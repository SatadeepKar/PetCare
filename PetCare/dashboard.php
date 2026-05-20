<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = get_db();
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST
$today = date("Y-m-d");

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$username = $user['username'];

// Fetch pets
$stmt = $conn->prepare("SELECT * FROM pets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets = $stmt->get_result();
$pets_count = $pets->num_rows;

// Stats: all pending reminders (not filter-dependent)
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE p.user_id = ? AND r.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_reminders_count = (int)$stmt->get_result()->fetch_assoc()['c'];

// Stats: completed reminders
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE p.user_id = ? AND r.status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_tasks_count = (int)$stmt->get_result()->fetch_assoc()['c'];

// Determine reminder filter (default to "today")
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$today = date("Y-m-d");
$reminder_query = "SELECT r.*, p.name AS pet_name FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE p.user_id = ? AND r.status = 'pending' ORDER BY r.due_date, r.due_time";
if ($filter === 'today') {
    $reminder_query = "SELECT r.*, p.name AS pet_name FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE p.user_id = ? AND r.due_date = ? AND r.status = 'pending' ORDER BY r.due_time";
}
$stmt = $conn->prepare($reminder_query);
if ($filter === 'today') {
    $stmt->bind_param("is", $user_id, $today);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$reminders = $stmt->get_result();

// Fetch calendar data (reminders, health records, journal entries)
$calendar_events = [];
$stmt = $conn->prepare("SELECT r.id, r.pet_id, r.task, r.due_date, r.due_time, r.type, r.status, p.name AS pet_name FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reminder_results = $stmt->get_result();
while ($row = $reminder_results->fetch_assoc()) {
    $calendar_events[] = [
        'title' => $row['task'] . ' (' . $row['pet_name'] . ')',
        'start' => $row['due_date'] . 'T' . $row['due_time'],
        'type' => $row['type'],
        'status' => $row['status'],
        'id' => $row['id'],
        'pet_id' => $row['pet_id']
    ];
    error_log("Reminder: " . print_r($row, true));
}

$stmt = $conn->prepare("SELECT id, pet_id, type, details, date FROM health_records WHERE pet_id IN (SELECT id FROM pets WHERE user_id = ?)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$health_results = $stmt->get_result();
while ($row = $health_results->fetch_assoc()) {
    $calendar_events[] = [
        'title' => ucfirst(str_replace('_', ' ', $row['type'])) . ': ' . $row['details'],
        'start' => $row['date'],
        'type' => 'health_' . $row['type'],
        'status' => 'completed',
        'id' => $row['id'],
        'pet_id' => $row['pet_id']
    ];
}

$stmt = $conn->prepare("SELECT id, pet_id, entry, entry_date FROM pet_journal WHERE pet_id IN (SELECT id FROM pets WHERE user_id = ?)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$journal_results = $stmt->get_result();
while ($row = $journal_results->fetch_assoc()) {
    $calendar_events[] = [
        'title' => 'Journal: ' . substr($row['entry'], 0, 20) . (strlen($row['entry']) > 20 ? '...' : ''),
        'start' => $row['entry_date'],
        'type' => 'journal',
        'status' => 'completed',
        'id' => $row['id'],
        'pet_id' => $row['pet_id']
    ];
}

// Handle reminder completion and deletion
if (isset($_GET['complete_reminder'])) {
    $reminder_id = (int)$_GET['complete_reminder'];
    $stmt = $conn->prepare("UPDATE reminders r JOIN pets p ON r.pet_id = p.id SET r.status = 'completed' WHERE r.id = ? AND p.user_id = ?");
    $stmt->bind_param("ii", $reminder_id, $user_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?filter=$filter&success=1");
        exit();
    }
} elseif (isset($_GET['delete_reminder'])) {
    $reminder_id = (int)$_GET['delete_reminder'];
    $stmt = $conn->prepare("DELETE r FROM reminders r JOIN pets p ON r.pet_id = p.id WHERE r.id = ? AND p.user_id = ?");
    $stmt->bind_param("ii", $reminder_id, $user_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?filter=$filter&success=1");
        exit();
    }
}

// Handle note addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $content = trim($_POST['content']);
    if ($content) {
        $stmt = $conn->prepare("INSERT INTO notes (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $content);
        if ($stmt->execute()) {
            header("Location: dashboard.php?filter=$filter&success=2");
            exit();
        } else {
            $error = "Failed to add note: " . $conn->error;
        }
    } else {
        $error = "Please enter a note.";
    }
}

// Handle note deletion
if (isset($_GET['delete_note'])) {
    $note_id = (int)$_GET['delete_note'];
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?filter=$filter&success=3");
        exit();
    } else {
        $error = "Failed to delete note: " . $conn->error;
    }
}

// Fetch notes
$stmt = $conn->prepare("SELECT id, content, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt === false) {
    $error = "Failed to prepare notes query: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notes = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                            950: '#3b0764',
                        },
                        secondary: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7e22ce',
                            800: '#6b21a8',
                            900: '#581c87',
                            950: '#3b0764',
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0 4px 15px -1px rgba(0, 0, 0, 0.1), 0 2px 10px -1px rgba(0, 0, 0, 0.05)',
                        'card-hover': '0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 15px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --bg-main: #f9f7ff;
            --bg-card: #ffffff;
            --text-primary: #4c1d95;
            --text-secondary: #7e22ce;
            --border-color: #e9d5ff;
            --header-gradient-from: #7e22ce;
            --header-gradient-to: #a855f7;
            --header-text: #ffffff;
        }
        
        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .bg-card {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        
        .text-primary-color {
            color: var(--text-primary);
        }
        
        .text-secondary-color {
            color: var(--text-secondary);
        }
        
        .header-gradient {
            background-image: linear-gradient(to right, var(--header-gradient-from), var(--header-gradient-to));
            color: var(--header-text);
        }
        
        .pet-card {
            position: relative;
            overflow: hidden;
        }
        
        .pet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: linear-gradient(135deg, #9333ea, #c084fc);
            opacity: 0.85;
            z-index: 0;
        }
        
        .pet-card-content {
            position: relative;
            z-index: 1;
        }
        
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .badge-medication {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-vet_appointment {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge-task {
            background-color: #f3e8ff;
            color: #7e22ce;
        }

        .badge-dog, .badge-Dog {
            background-color: #e0f2fe;
            color: #0284c7;
        }

        .badge-cat, .badge-Cat {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge-pending {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-completed {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .header-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            color: var(--header-text);
            font-weight: 500;
        }
        
        .header-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .header-button i {
            font-size: 1rem;
        }
        
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .action-button:hover {
            transform: translateY(-1px);
        }
        
        .action-button i {
            transition: transform 0.2s ease;
        }
        
        .action-button:hover i {
            transform: rotate(90deg);
        }
        
        .reminder-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .reminder-card:hover {
            transform: translateX(2px);
        }
        
        .reminder-card.medication {
            border-left-color: #dc2626;
        }

        .reminder-card.vet_appointment {
            border-left-color: #d97706;
        }

        .reminder-card.task {
            border-left-color: #9333ea;
        }
        
        .pet-avatar {
            position: relative;
            display: inline-block;
        }
        
        .pet-avatar::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background-color: #10b981;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .pet-card-container {
            transition: all 0.3s ease;
        }
        
        .pet-card-container:hover {
            transform: translateY(-5px);
        }
        
        .complete-button {
            transition: all 0.2s ease;
        }
        
        .complete-button:hover {
            transform: scale(1.2);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f3e8ff;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c084fc;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #9333ea;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }

        /* Auto-remove alert animation */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Calendar styles */
        #calendar {
            min-height: 400px;
            font-size: 0.875rem;
            max-width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .fc {
            --fc-border-color: #e9d5ff;
            --fc-button-bg-color: #9333ea;
            --fc-button-border-color: #9333ea;
            --fc-button-hover-bg-color: #7e22ce;
            --fc-button-hover-border-color: #7e22ce;
            --fc-button-active-bg-color: #7e22ce;
            --fc-button-active-border-color: #7e22ce;
            --fc-event-bg-color: #9333ea;
            --fc-event-border-color: #9333ea;
            --fc-today-bg-color: #f3e8ff;
            font-family: 'Poppins', sans-serif;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4c1d95;
        }
        
        .fc .fc-button {
            font-weight: 500;
            text-transform: capitalize;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .fc .fc-daygrid-day-number {
            padding: 6px 8px;
            font-weight: 500;
            color: #4c1d95;
        }
        
        .fc .fc-daygrid-day {
            border: 1px solid #e9d5ff;
            transition: background-color 0.2s;
        }
        
        .fc .fc-daygrid-day:hover {
            background-color: #faf5ff;
        }
        
        .fc .fc-daygrid-day-events {
            margin-top: 2px;
            padding: 0 2px;
        }
        
        .fc .fc-event {
            border: none;
            background-color: transparent;
            font-size: 0.75rem;
            padding: 2px 4px;
            border-radius: 4px;
            margin-bottom: 1px;
        }
        
        .fc .fc-daygrid-event-dot {
            margin-right: 4px;
            border-width: 4px;
        }
        
        .fc .fc-day-today {
            background-color: #f3e8ff !important;
        }
        
        .fc .fc-highlight {
            background-color: #f3e8ff;
        }
        
        .fc .fc-col-header-cell {
            background-color: #f5f3ff;
            padding: 8px 0;
        }
        
        .fc .fc-col-header-cell-cushion {
            font-weight: 600;
            color: #6b21a8;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: var(--bg-card);
            padding: 24px;
            border-radius: 16px;
            width: 400px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: var(--text-primary);
            animation: fadeIn 0.3s ease;
            position: relative;
            border: 1px solid #e9d5ff;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            cursor: pointer;
            color: #9333ea;
            font-size: 1.25rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f3e8ff;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background-color: #e9d5ff;
            color: #7e22ce;
            transform: rotate(90deg);
        }
        
        .modal p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .modal p strong {
            min-width: 80px;
            display: inline-block;
            color: #7e22ce;
        }

        /* Notes FAB styles */
        .notes-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            background-color: #9333ea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .notes-fab:hover {
            background-color: #7e22ce;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Notes list styles */
        .note-item {
            padding: 12px;
            border-bottom: 1px solid #e9d5ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }

        .note-item:hover {
            background-color: #faf5ff;
        }

        .note-item:last-child {
            border-bottom: none;
        }

        .note-content {
            flex: 1;
            color: #4c1d95;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .note-meta {
            color: #7e22ce;
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .note-delete {
            color: #dc2626;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .note-delete:hover {
            background-color: #fee2e2;
            transform: scale(1.1);
        }

        /* Notification styles */
        .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            transition: all 0.2s;
        }
        
        .notification-bell:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .notification-banner {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
            color: var(--text-primary);
            margin-top: 12px;
        }
        
        .notification-banner:before {
            content: '';
            position: absolute;
            top: -8px;
            right: 16px;
            width: 16px;
            height: 16px;
            background-color: var(--bg-card);
            transform: rotate(45deg);
            border-top: 1px solid var(--border-color);
            border-left: 1px solid var(--border-color);
        }

        .notification-item {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .notification-item:hover {
            background-color: #faf5ff;
        }

        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item p {
            margin: 4px 0;
        }
        
        .notification-item p:first-child {
            font-weight: 600;
            color: #7e22ce;
        }
        
        .notification-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h3 {
            font-weight: 600;
            color: #7e22ce;
            font-size: 1rem;
        }
        
        .notification-header button {
            background: none;
            border: none;
            color: #9333ea;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .notification-header button:hover {
            color: #7e22ce;
            text-decoration: underline;
        }
        
        .notification-empty {
            padding: 24px 16px;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 280px;
            background-color: white;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
            backdrop-filter: blur(2px);
        }
        
        .sidebar-header {
            padding: 20px;
            background-image: linear-gradient(to right, var(--header-gradient-from), var(--header-gradient-to));
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .sidebar-header h2 {
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .sidebar-content {
            padding: 16px;
            flex: 1;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4c1d95;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #f3e8ff;
            color: #7e22ce;
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #e9d5ff;
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4c1d95;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .sidebar-footer a:hover {
            background-color: #f3e8ff;
            color: #7e22ce;
        }
        
        .sidebar-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sidebar-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        /* User profile in sidebar */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid #e9d5ff;
            margin-bottom: 16px;
        }
        
        .user-profile img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-profile-info h3 {
            font-weight: 600;
            color: #4c1d95;
            margin: 0;
        }
        
        .user-profile-info p {
            color: #7e22ce;
            font-size: 0.875rem;
            margin: 0;
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9d5ff;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: #f3e8ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7e22ce;
            font-size: 1.5rem;
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4c1d95;
            margin-bottom: 4px;
        }
        
        .stat-card-label {
            color: #7e22ce;
            font-size: 0.875rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="font-sans">
    <!-- Sidebar for Mobile Menu -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="Uploads/logo.jpg" alt="PetCare Logo" onerror="this.src='https://via.placeholder.com/48?text=PC';">
            <h2>PetCare</h2>
            <button class="sidebar-close" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="user-profile">
            <img src="https://via.placeholder.com/48?text=<?php echo substr($username, 0, 1); ?>" alt="User Profile">
            <div class="user-profile-info">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <p>Pet Owner</p>
            </div>
        </div>
        
        <div class="sidebar-content">
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="diet_tracker.php">
                        <i class="fas fa-utensils"></i>
                        <span>Diet Tracker</span>
                    </a>
                </li>
                <li>
                    <a href="gallery.php">
                        <i class="far fa-images"></i>
                        <span>Gallery</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo VET_LOCATOR_URL; ?>" target="_blank" rel="noopener">
                        <i class="fas fa-stethoscope"></i>
                        <span>Vet Locator</span>
                    </a>
                </li>
                <li>
                    <a href="set_reminder.php">
                        <i class="fas fa-bell"></i>
                        <span>Set Reminders</span>
                    </a>
                </li>
                <li>
                    <a href="add_pet.php">
                        <i class="fas fa-paw"></i>
                        <span>Add New Pet</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="min-h-screen flex flex-col">
        <!-- Header/Navigation -->
        <header class="header-gradient shadow-lg sticky top-0 z-10">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <button class="md:hidden text-white p-2 rounded-full hover:bg-white/20 transition-colors" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="bg-white p-2 rounded-full shadow-md">
                            <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-10 h-10 rounded-full object-cover" onerror="this.src='https://via.placeholder.com/48?text=PC';">
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">PetCare</h1>
                    </div>
                    
                    <nav class="hidden md:flex items-center space-x-4">
                        <a href="diet_tracker.php" class="header-button">
                            <i class="fas fa-utensils"></i>
                            <span>Diet Tracker</span>
                        </a>
                        <a href="gallery.php" class="header-button">
                            <i class="far fa-images"></i>
                            <span>Gallery</span>
                        </a>
                        <a href="<?php echo VET_LOCATOR_URL; ?>" class="header-button" target="_blank" rel="noopener">
                            <i class="fas fa-stethoscope"></i>
                            <span>Vet Locator</span>
                        </a>
                        <a href="settings.php" class="header-button">
                            <i class="fas fa-gear"></i>
                            <span>Settings</span>
                        </a>
                        <a href="logout.php" class="header-button">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </nav>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notification Bell with Banner and Count -->
                        <div class="notification-bell relative" id="notificationBell">
                            <i class="fas fa-bell text-white text-xl"></i>
                            <span class="notification-count hidden" id="notificationCount">0</span>
                            <span class="notification-badge hidden" id="notificationBadge"></span>
                            <div id="notificationBanner" class="notification-banner">
                                <div class="notification-header">
                                    <h3>Notifications</h3>
                                    <button id="clearAllNotifications">Clear All</button>
                                </div>
                                <div id="notificationItems">
                                    <!-- Notifications will be populated dynamically -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="hidden md:flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo substr($username, 0, 1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="container mx-auto px-4 py-8 flex-1">
            <!-- Welcome Section -->
            <section class="mb-8 animate-fadeIn">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                        <i class="fas fa-paw text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-primary-color">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                        <p class="text-secondary-color">Manage your pets and their care schedules below.</p>
                    </div>
                </div>
            </section>
            
            <!-- Stats Overview -->
            <div class="stats-grid animate-fadeIn delay-100">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-dog"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $pets_count; ?></div>
                    <div class="stat-card-label">Total Pets</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $active_reminders_count; ?></div>
                    <div class="stat-card-label">Active Reminders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $completed_tasks_count; ?></div>
                    <div class="stat-card-label">Completed Tasks</div>
                </div>
            </div>
            
            <!-- Pets Section -->
            <div class="bg-card rounded-2xl shadow-card p-6 mb-8 animate-fadeIn delay-100">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                            <i class="fas fa-dog"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-primary-color">Your Pets</h2>
                    </div>
                    <a href="add_pet.php" class="action-button bg-primary-600 hover:bg-primary-700 text-white shadow-md hover:shadow-lg w-full sm:w-auto justify-center sm:justify-start">
                        <i class="fas fa-plus"></i>
                        <span>Add a New Pet</span>
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $pets->data_seek(0); // Reset pointer
                    $delay = 100;
                    while ($pet = $pets->fetch_assoc()): 
                        $delay += 100;
                    ?>
                    <a href="pet_profile.php?pet_id=<?php echo $pet['id']; ?>" class="block pet-card-container animate-fadeIn" style="animation-delay: <?php echo $delay; ?>ms;">
                        <div class="rounded-2xl overflow-hidden shadow-card hover:shadow-card-hover border border-gray-100 h-full">
                            <div class="pet-card h-40 relative">
                                <div class="pet-card-content h-full flex flex-col justify-center items-center p-4">
                                    <div class="w-24 h-24 bg-white rounded-full p-1 shadow-lg mb-2">
                                        <img src="<?php echo $pet['photo'] ? htmlspecialchars($pet['photo']) : 'https://via.placeholder.com/150?text=' . substr($pet['name'], 0, 1); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" class="w-full h-full rounded-full object-cover">
                                    </div>
                                    <h3 class="text-xl font-bold text-white drop-shadow-md"><?php echo htmlspecialchars($pet['name']); ?></h3>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2 mb-1">
                                            <?php if ($pet['species'] == 'Dog'): ?>
                                                <i class="fas fa-dog text-primary-500"></i>
                                            <?php elseif ($pet['species'] == 'Cat'): ?>
                                                <i class="fas fa-cat text-secondary-500"></i>
                                            <?php else: ?>
                                                <i class="fas fa-paw text-gray-500"></i>
                                            <?php endif; ?>
                                            <p class="text-secondary-color font-medium">
                                                <?php echo htmlspecialchars($pet['species']); ?>
                                                <?php echo $pet['breed'] ? ' • ' . htmlspecialchars($pet['breed']) : ''; ?>
                                            </p>
                                        </div>
                                        <?php if ($pet['age']): ?>
                                        <div class="flex items-center space-x-2 text-secondary-color text-sm">
                                            <i class="fas fa-birthday-cake text-gray-400"></i>
                                            <span><?php echo $pet['age']; ?> years old</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge badge-<?php echo strtolower($pet['species']); ?>">
                                        <?php if ($pet['species'] == 'Dog'): ?>
                                            <i class="fas fa-bone"></i>
                                        <?php elseif ($pet['species'] == 'Cat'): ?>
                                            <i class="fas fa-fish"></i>
                                        <?php else: ?>
                                            <i class="fas fa-paw"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($pet['species']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Upcoming Reminders Section -->
            <div class="bg-card rounded-2xl shadow-card p-6 mb-8 animate-fadeIn delay-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-primary-color">Upcoming Reminders</h2>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                        <a href="set_reminder.php" class="action-button bg-primary-600 hover:bg-primary-700 text-white shadow-md hover:shadow-lg w-full sm:w-auto justify-center sm:justify-start">
                            <i class="fas fa-plus"></i>
                            <span>Set a New Reminder</span>
                        </a>
                        <div class="relative">
                            <select name="filter" onchange="window.location.href='dashboard.php?filter=' + this.value" class="appearance-none pl-4 pr-10 py-2.5 border border-purple-200 rounded-lg shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-opacity-20 bg-white text-gray-700 font-medium w-full">
                                <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Today's Reminders</option>
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Reminders</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <?php if ($reminders->num_rows > 0): ?>
                        <?php 
                        $delay = 200;
                        while ($reminder = $reminders->fetch_assoc()): 
                            $delay += 100;
                            $dueDateTime = new DateTime("{$reminder['due_date']} {$reminder['due_time']}");
                            $now = new DateTime();
                            $isDue = $dueDateTime <= $now;
                            $timeElapsed = $isDue ? '<span class="text-red-600 font-semibold">Time Elapsed</span>' : '';

                            $reminderType = $reminder['type'];
                        ?>
                            <div class="reminder-card <?php echo $reminderType; ?> border border-gray-100 rounded-xl p-4 flex justify-between items-center <?php echo $isDue ? 'bg-red-50' : 'bg-white'; ?> shadow-sm hover:shadow-md transition-all animate-fadeIn" style="animation-delay: <?php echo $delay; ?>ms;">
                                <div class="flex items-start space-x-4">
                                    <a href="javascript:void(0)" class="complete-button text-green-500 hover:text-green-600 bg-green-100 hover:bg-green-200 p-2 rounded-full transition-colors" onclick="confirmComplete(<?php echo $reminder['id']; ?>, '<?php echo $filter; ?>')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <div>
                                        <h3 class="font-semibold text-primary-color text-lg <?php echo $isDue ? 'text-red-600' : ''; ?>">
                                            <?php echo htmlspecialchars($reminder['task']); ?>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                            <div class="pet-avatar">
                                                <div class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold shadow-md">
                                                    <?php echo substr($reminder['pet_name'], 0, 1); ?>
                                                </div>
                                            </div>
                                            <span class="text-secondary-color"><?php echo htmlspecialchars($reminder['pet_name']); ?></span>
                                            <span class="text-gray-500 text-sm"><?php echo $reminder['due_date'] . ' at ' . $reminder['due_time']; ?></span>
                                            <span class="badge badge-<?php echo $reminderType; ?>">
                                                <?php if ($reminderType == 'medication'): ?>
                                                    <i class="fas fa-pills"></i>
                                                <?php elseif ($reminderType == 'vet_appointment'): ?>
                                                    <i class="fas fa-stethoscope"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-tasks"></i>
                                                <?php endif; ?>
                                                <?php echo ucwords(str_replace('_', ' ', $reminderType)); ?>
                                            </span>
                                            <?php if ($reminder['recurrence']): ?>
                                                <span class="text-gray-500 text-sm"><?php echo '(' . htmlspecialchars($reminder['recurrence']) . ')'; ?></span>
                                            <?php endif; ?>
                                            <?php echo $timeElapsed; ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:void(0)" class="text-red-500 hover:text-red-600 bg-red-100 hover:bg-red-200 p-2 rounded-full transition-colors" onclick="confirmDelete(<?php echo $reminder['id']; ?>, '<?php echo $filter; ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-secondary-color bg-purple-50 rounded-xl border border-purple-100">
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mx-auto mb-4">
                                <i class="fas fa-check-circle text-2xl"></i>
                            </div>
                            <p class="text-lg font-medium">No upcoming reminders</p>
                            <p class="text-sm mt-2">All caught up! Add a new reminder to stay on track.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div id="successAlert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mt-4 flex items-center" role="alert">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        <p>Reminder updated successfully!</p>
                    </div>
                <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
                    <div id="successAlert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mt-4 flex items-center" role="alert">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        <p>Note added successfully!</p>
                    </div>
                <?php elseif (isset($_GET['success']) && $_GET['success'] == 3): ?>
                    <div id="successAlert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mt-4 flex items-center" role="alert">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        <p>Note deleted successfully!</p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div id="errorAlert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mt-4 flex items-center" role="alert">
                        <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pet Care Calendar Section -->
            <div class="bg-card rounded-2xl shadow-card p-6 animate-fadeIn delay-300">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-primary-color">Pet Care Calendar</h2>
                    </div>
                </div>
                
                <div id="calendar" class="w-full"></div>
            </div>
        </main>
        
        <footer class="bg-white py-6 mt-auto border-t border-purple-100">
            <div class="container mx-auto px-4 text-center">
                <div class="flex justify-center items-center mb-4">
                    <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-10 h-10 rounded-full object-cover mr-2" onerror="this.src='https://via.placeholder.com/48?text=PC';">
                    <span class="text-xl font-bold text-primary-color">PetCare</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">Taking care of your pets, one reminder at a time.</p>
                <div class="flex justify-center space-x-6 mb-4">
                    <a href="#" class="text-primary-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-facebook-f text-xl"></i>
                    </a>
                    <a href="#" class="text-primary-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-primary-500 hover:text-primary-600 transition-colors">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                </div>
                <p class="text-gray-500 text-sm">© 2025 PetCare. All rights reserved.</p>
            </div>
        </footer>

        <!-- Notes FAB -->
        <button class="notes-fab" onclick="document.getElementById('notesModal').style.display='flex'">
            <i class="fas fa-note-sticky"></i>
        </button>

        <!-- Modal for Event Details -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="document.getElementById('eventModal').style.display='none'">
                    <i class="fas fa-times"></i>
                </span>
                <h3 class="text-xl font-bold text-primary-color mb-4">Event Details</h3>
                <p class="mb-3"><strong>Title:</strong> <span id="modalTitle"></span></p>
                <p class="mb-3"><strong>Date:</strong> <span id="modalDate"></span></p>
                <p class="mb-3"><strong>Type:</strong> <span id="modalType" class="badge"></span></p>
                <p class="mb-3"><strong>Status:</strong> <span id="modalStatus" class="badge"></span></p>
                <div class="flex justify-end mt-6">
                    <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors" onclick="document.getElementById('eventModal').style.display='none'">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal for Notes -->
        <div id="notesModal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="document.getElementById('notesModal').style.display='none'">
                    <i class="fas fa-times"></i>
                </span>
                <h3 class="text-xl font-bold text-primary-color mb-4">My Notes</h3>
                <!-- Add Note Form -->
                <form method="POST" action="dashboard.php" class="mb-6">
                    <div class="mb-4">
                        <label for="content" class="block text-sm font-medium text-secondary-color mb-1">New Note</label>
                        <textarea name="content" id="content" class="w-full px-3 py-2 border border-purple-200 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-opacity-20" rows="3" required placeholder="Write your note here..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_note" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            Add Note
                        </button>
                    </div>
                </form>
                <!-- Notes List -->
                <div class="notes-list">
                    <h4 class="text-lg font-semibold text-secondary-color mb-3">Existing Notes</h4>
                    <?php if (isset($notes) && $notes->num_rows > 0): ?>
                        <?php while ($note = $notes->fetch_assoc()): ?>
                            <div class="note-item">
                                <div>
                                    <p class="note-content"><?php echo htmlspecialchars($note['content']); ?></p>
                                    <p class="note-meta">Added on <?php echo date('M d, Y, h:i A', strtotime($note['created_at'])); ?></p>
                                </div>
                                <a href="javascript:void(0)" class="note-delete" onclick="confirmDeleteNote(<?php echo $note['id']; ?>, '<?php echo $filter; ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-500 text-sm">No notes yet. Add one above!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-remove alerts after 3 seconds
        setTimeout(function() {
            var successAlert = document.getElementById('successAlert');
            var errorAlert = document.getElementById('errorAlert');
            if (successAlert) {
                successAlert.style.animation = 'fadeOut 0.5s forwards';
                setTimeout(function() { successAlert.remove(); }, 500);
            }
            if (errorAlert) {
                errorAlert.style.animation = 'fadeOut 0.5s forwards';
                setTimeout(function() { errorAlert.remove(); }, 500);
            }
        }, 3000);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('active');
            
            if (sidebar.classList.contains('active')) {
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Confirm and complete reminder
        function confirmComplete(reminderId, filter) {
            if (confirm('Are you sure you want to mark this reminder as completed?')) {
                window.location.href = `dashboard.php?complete_reminder=${reminderId}&filter=${filter}`;
            }
        }

        // Confirm and delete reminder
        function confirmDelete(reminderId, filter) {
            if (confirm('Are you sure you want to delete this reminder?')) {
                window.location.href = `dashboard.php?delete_reminder=${reminderId}&filter=${filter}`;
            }
        }

        // Confirm and delete note
        function confirmDeleteNote(noteId, filter) {
            if (confirm('Are you sure you want to delete this note?')) {
                window.location.href = `dashboard.php?delete_note=${noteId}&filter=${filter}`;
            }
        }

        // Initialize FullCalendar with modal
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: <?php echo json_encode($calendar_events); ?>,
                eventContent: function(arg) {
                    let bgColor;
                    let icon = '';
                    
                    if (arg.event.extendedProps.status === 'pending') {
                        bgColor = '#ef4444';
                    } else if (arg.event.extendedProps.type.startsWith('health_')) {
                        bgColor = '#9333ea';
                        icon = '<i class="fas fa-heartbeat mr-1"></i>';
                    } else if (arg.event.extendedProps.type === 'journal') {
                        bgColor = '#6b7280';
                        icon = '<i class="fas fa-book mr-1"></i>';
                    } else if (arg.event.extendedProps.type === 'medication') {
                        bgColor = '#dc2626';
                        icon = '<i class="fas fa-pills mr-1"></i>';
                    } else if (arg.event.extendedProps.type === 'vet_appointment') {
                        bgColor = '#d97706';
                        icon = '<i class="fas fa-stethoscope mr-1"></i>';
                    } else {
                        bgColor = '#10b981';
                        icon = '<i class="fas fa-tasks mr-1"></i>';
                    }
                    
                    return { 
                        html: `<div class="fc-event-custom" style="background-color: ${bgColor}; color: white; padding: 2px 4px; border-radius: 4px; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${icon}${arg.event.title}</div>` 
                    };
                },
                dayMaxEvents: true,
                moreLinkClick: 'popover',
                height: 'auto',
                aspectRatio: 1.5,
                eventDidMount: function(info) {
                    // Add tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'calendar-tooltip';
                    tooltip.innerHTML = `
                        <strong>${info.event.title}</strong><br>
                        ${new Date(info.event.start).toLocaleString()}
                    `;
                    
                    info.el.addEventListener('mouseover', function() {
                        info.el.style.cursor = 'pointer';
                    });
                },
                eventClick: function(info) {
                    var event = info.event;
                    document.getElementById('modalTitle').textContent = event.title;
                    document.getElementById('modalDate').textContent = event.start ? event.start.toLocaleString() : 'No date specified';
                    
                    const typeEl = document.getElementById('modalType');
                    typeEl.textContent = event.extendedProps.type.replace(/_/g, ' ');
                    typeEl.className = 'badge badge-' + event.extendedProps.type;
                    
                    const statusEl = document.getElementById('modalStatus');
                    statusEl.textContent = event.extendedProps.status;
                    statusEl.className = 'badge badge-' + event.extendedProps.status;
                    
                    document.getElementById('eventModal').style.display = 'flex';
                }
            });
            calendar.render();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            var eventModal = document.getElementById('eventModal');
            var notesModal = document.getElementById('notesModal');
            if (event.target == eventModal) {
                eventModal.style.display = 'none';
            }
            if (event.target == notesModal) {
                notesModal.style.display = 'none';
            }
        }

        // Enhanced Notification System
        const notificationBell = document.getElementById('notificationBell');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationCount = document.getElementById('notificationCount');
        const notificationBanner = document.getElementById('notificationBanner');
        const notificationItems = document.getElementById('notificationItems');
        const clearAllBtn = document.getElementById('clearAllNotifications');
        let notifiedEvents = new Set();

        // Audio for notifications
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

        function checkNotifications() {
            const now = new Date();
            let hasNotifications = false;
            let notificationCountValue = 0;
            
            // Clear previous notifications
            notificationItems.innerHTML = '';

            const events = <?php echo json_encode($calendar_events); ?>;
            
            if (!events || events.length === 0) {
                notificationItems.innerHTML = '<div class="notification-empty">No notifications at this time</div>';
                notificationBadge.classList.add('hidden');
                notificationCount.classList.add('hidden');
                return;
            }

            events.forEach(event => {
                if (event.status === 'pending') {
                    const eventTime = new Date(event.start);
                    if (isNaN(eventTime)) {
                        console.error('Invalid Date for event:', event);
                        return;
                    }
                    
                    const timeDiffMs = eventTime - now;
                    const oneHourMs = 60 * 60 * 1000;
                    const fifteenMinMs = 15 * 60 * 1000;
                    const fiveMinMs = 5 * 60 * 1000;
                    const eventId = event.id + '_' + event.start;
                    
                    // Check if event is within notification windows
                    if (timeDiffMs > 0 && (timeDiffMs <= oneHourMs || timeDiffMs <= fifteenMinMs || timeDiffMs <= fiveMinMs)) {
                        if (!notifiedEvents.has(eventId)) {
                            notifiedEvents.add(eventId);
                            hasNotifications = true;
                            notificationCountValue++;
                            
                            // Create notification item
                            const item = document.createElement('div');
                            item.className = 'notification-item';
                            
                            // Format the time remaining
                            let timeMessage = '';
                            if (timeDiffMs <= fiveMinMs) {
                                timeMessage = 'In less than 5 minutes!';
                            } else if (timeDiffMs <= fifteenMinMs) {
                                timeMessage = 'In less than 15 minutes';
                            } else if (timeDiffMs <= oneHourMs) {
                                timeMessage = 'In less than 1 hour';
                            }
                            
                            // Get icon based on type
                            let icon = '';
                            if (event.type === 'medication') {
                                icon = '<i class="fas fa-pills mr-2 text-red-500"></i>';
                            } else if (event.type === 'vet_appointment') {
                                icon = '<i class="fas fa-stethoscope mr-2 text-amber-500"></i>';
                            } else {
                                icon = '<i class="fas fa-tasks mr-2 text-purple-500"></i>';
                            }
                            
                            item.innerHTML = `
                                <p>${icon}<strong>${event.title || 'Untitled Event'}</strong></p>
                                <p><i class="far fa-clock mr-2 text-purple-400"></i>${timeMessage}</p>
                                <p><i class="far fa-calendar-alt mr-2 text-purple-400"></i>${eventTime.toLocaleString()}</p>
                            `;
                            notificationItems.appendChild(item);
                            
                            // Play sound for new notifications
                            audio.play().catch(error => console.log("Audio play failed:", error));
                        }
                    }
                }
            });

            // Update notification UI
            notificationBadge.classList.toggle('hidden', !hasNotifications);
            notificationCount.textContent = notificationCountValue;
            notificationCount.classList.toggle('hidden', notificationCountValue === 0);
            
            // Add empty state if no notifications
            if (notificationCountValue === 0) {
                notificationItems.innerHTML = '<div class="notification-empty">No notifications at this time</div>';
            }
            
            // Add animation effect if there are notifications
            if (hasNotifications) {
                notificationBell.classList.add('animate-bounce');
                setTimeout(() => notificationBell.classList.remove('animate-bounce'), 1000);
            }
        }

        // Toggle notification banner
        notificationBell.addEventListener('click', () => {
            notificationBanner.style.display = notificationBanner.style.display === 'block' ? 'none' : 'block';
            if (notificationBanner.style.display === 'block') {
                checkNotifications(); // Refresh notifications when opening the banner
            }
        });

        // Clear all notifications
        clearAllBtn.addEventListener('click', () => {
            notifiedEvents.clear();
            notificationItems.innerHTML = '<div class="notification-empty">No notifications at this time</div>';
            notificationBadge.classList.add('hidden');
            notificationCount.classList.add('hidden');
        });

        // Hide banner when clicking outside
        document.addEventListener('click', (event) => {
            if (!notificationBell.contains(event.target) && !notificationBanner.contains(event.target)) {
                notificationBanner.style.display = 'none';
            }
        });

        // Check notifications every minute
        setInterval(checkNotifications, 60000);

        // Initial check
        checkNotifications();
    </script>
</body>
</html>

<?php
$conn->close();
?>