<?php
// TopHeader.php - Complete working implementation with integrated profile and settings modals
class TopHeader {
    private $user;
    private $isDarkMode;
    private $currentPath;
    
    public function __construct($user, $notifications = [], $isDarkMode = false, $currentPath = '/') {
        $this->user = $user;
        $this->isDarkMode = $isDarkMode;
        $this->currentPath = $currentPath;
    }
    
    public function render() {
        $darkModeActive = $this->isDarkModeActive();
        $userInitials = $this->getUserInitials();

        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Header</title>
    <style>
        :root {
            --header-bg: #5ab89a;
            --panel-bg: #ffffff;
            --input-bg: #f8f9fa;
            --border-color: #e0e0e0;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --primary-color: #2EB28A;
            --primary-dark: #259c78;
            --primary-light: #a8f0e6;
            --secondary-bg: #f1f5f9;
            --secondary-hover: #e2e8f0;
            --active-bg: rgba(46, 178, 138, 0.05);
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dark-mode {
            --header-bg: #5ab89a;
            --panel-bg: #2d2d2d;
            --bg-card: #242424e8;
            --input-bg: #3d3d3d;
            --border-color: #444;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --primary-color: #2EB28A;
            --primary-dark: #259c78;
            --primary-light: #03332d;
            --secondary-bg: #3d3d3d;
            --secondary-hover: #4d4d4d;
            --active-bg: rgba(46, 178, 138, 0.1);
            --shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background-color: var(--panel-bg);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .top-header {
            background: var(--header-bg);
            box-shadow: var(--shadow);
            border-bottom: 1px solid var(--border-color);
            position: relative;
            z-index: 100;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 120px;
            /*min-height: 80px;*/
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .logo-placeholder {
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: opacity 0.3s ease;
            padding: 8px 12px;
            border-radius: 8px;
        }

        /* .logo-placeholder:hover {
            opacity: 0.8;
            background: var(--input-bg);
        } */

.logo-image {
    width: 180px;
    height: 20%;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    /* Remove any background or border that might be hiding the logo */
    background: none;
    border: none;
}



.logo-img {
    width: 100%;
    height: 50%;
    object-fit: cover;
    /* Ensure no borders or padding interfere */
    border: none;
    padding: 0;
    margin: 0;
}

        .header-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            background: none;
            border: none;
            color: var(--text-primary);
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 0.95em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .nav-link:hover {
            background: var(--input-bg);
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .desktop-view {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-welcome-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .welcome-text {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.95em;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            padding: 12px 16px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .welcome-text:hover {
            background: var(--input-bg);
        }

        .welcome-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--panel-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            z-index: 1000;
            margin-top: 4px;
            min-width: 220px;
            overflow: hidden;
            display: none; /* Hidden by default */
        }

        .welcome-dropdown.show {
            display: block; /* Show when has 'show' class */
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            width: 100%;
            background: none;
            border: none;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 0.95em;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: var(--input-bg);
        }

        .dropdown-icon {
            font-size: 1.2em;
            width: 22px;
            text-align: center;
        }

        .dropdown-text {
            flex: 1;
            text-align: left;
        }

        .dropdown-badge {
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75em;
            font-weight: 600;
        }

        .logout-item {
            color: #ef4444;
        }

        .logout-item:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1em;
            cursor: pointer;
            border: 2px solid var(--primary-light);
            font-family: 'Poppins', sans-serif;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .mobile-view {
            display: none;
        }

        .mobile-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9em;
            cursor: pointer;
            border: 2px solid var(--primary-light);
            font-family: 'Poppins', sans-serif;
            flex-shrink: 0;
        }

        .mobile-menu-btn {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .mobile-menu-btn:hover {
            background: var(--secondary-hover);
        }

        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            justify-content: flex-end;
            display: none;
        }

        .mobile-menu-content {
            width: 300px;
            height: 100%;
            background: var(--panel-bg);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.3);
            overflow-y: auto;
        }

        .mobile-menu-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mobile-user-avatar-large {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1em;
        }

        .mobile-user-details {
            display: flex;
            flex-direction: column;
        }

        .mobile-user-name {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95em;
        }

        .mobile-user-role {
            color: var(--text-secondary);
            font-size: 0.8em;
        }

        .close-mobile-menu {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.8em;
            cursor: pointer;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-nav-section {
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .mobile-nav-title {
            color: var(--text-secondary);
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 20px 10px;
            margin: 0;
        }

        .mobile-menu-actions {
            padding: 20px 0;
        }

        .mobile-menu-item {
            width: 100%;
            background: none;
            border: none;
            color: var(--text-primary);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.95em;
            position: relative;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
        }

        .mobile-menu-item.active {
            background: var(--active-bg);
            border-left: 4px solid var(--primary-color);
        }

        .mobile-menu-item:hover {
            background: var(--input-bg);
        }

        .mobile-menu-icon {
            font-size: 1.2em;
            width: 20px;
            text-align: center;
        }

        .logout-mobile {
            color: #ef4444;
        }

        /* Profile Modal Styles */
        .profile-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
            display: none;
        }

        .profile-modal-content {
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
        }

        /* Settings Modal Styles */
        .settings-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
            display: none;
        }

        .settings-modal-content {
            max-width: 500px;
            width: 90%;
            background: var(--panel-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { 
                opacity: 0; 
                transform: translateY(-20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        .profile-modal-header {
            background: linear-gradient(135deg, 
                rgba(46, 178, 138, 0.9) 0%, 
                rgba(36, 138, 108, 0.85) 100%);
            color: white;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .settings-modal-header {
            display: flex;
            align-items: center;
            padding: 30px 30px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-modal-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.3), 
                rgba(255, 255, 255, 0.15));
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
            flex-shrink: 0;
            font-family: 'Poppins', sans-serif;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }

        .settings-modal-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            margin-right: 15px;
        }

        .profile-modal-header-text h2 {
            font-size: 24px;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
            color: white;
            font-weight: 600;
        }

        .settings-modal-header-text h2 {
            margin: 0;
            font-size: 1.5em;
            color: var(--text-primary);
        }

        .profile-modal-header-text p {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 0;
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
        }

        .settings-modal-header-text p {
            margin: 5px 0 0;
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .profile-modal-body {
            padding: 30px 25px;
            background: var(--panel-bg);
        }

        .settings-modal-body {
            padding: 20px 30px 30px;
        }

        .info-row {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
            transition: all 0.2s ease;
        }

        .info-row:hover {
            border-bottom-color: var(--primary-color);
            transform: translateX(4px);
        }

        .info-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            width: 140px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95em;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.3px;
        }

        .info-value {
            flex: 1;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95em;
            font-weight: 500;
            padding-left: 10px;
        }

        .profile-close-btn, .settings-close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
            z-index: 10;
        }

        .profile-close-btn {
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 22px;
            width: 40px;
            height: 40px;
        }

        .profile-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        .settings-close-btn:hover {
            background: var(--input-bg);
            color: var(--text-primary);
        }

        /* Settings Modal Specific Styles */
        .user-info-section,
        .password-section {
            margin-bottom: 30px;
        }

        .user-info-section h3,
        .password-section h3 {
            margin: 0 0 15px 0;
            font-size: 1.1em;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .read-only-info {
            background: var(--input-bg);
            border-radius: 8px;
            padding: 15px;
        }

        .info-item {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-item label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9em;
            min-width: 120px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9em;
        }

        .password-input-container {
            position: relative;
            width: 100%;
        }

        .password-input-container input {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
            box-sizing: border-box;
            background: var(--input-bg);
            color: var(--text-primary);
        }

        .password-input-container input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(6, 195, 167, 0.1);
        }

        .password-input-container input.error {
            border-color: #ef4444;
        }

        .password-input-container input.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            color: var(--text-secondary);
        }

        .password-toggle:hover {
            background: var(--input-bg);
        }

        .error-message {
            color: #ef4444;
            font-size: 0.8em;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }

        .btn-secondary {
            background: var(--input-bg);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: var(--input-bg);
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        @media (max-width: 768px) {
            .desktop-view {
                display: none;
            }
            
            .mobile-view {
                display: flex;
            }

            .header-content {
                padding: 0 16px;
                min-height: 70px;
            }

            .header-left {
                gap: 20px;
            }

            .logo-image {
                width: 150px;
                height: 50px;
            }

            .header-nav {
                display: none;
            }

            .mobile-user-avatar {
                width: 40px;
                height: 40px;
            }

            .mobile-menu-btn {
                width: 40px;
                height: 40px;
            }

            .profile-modal-content,
            .settings-modal-content {
                margin: 10px;
                max-width: 95%;
            }

            .profile-modal-header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .settings-modal-header {
                padding: 20px 20px 15px;
                flex-direction: column;
                text-align: center;
            }

            .profile-modal-avatar {
                width: 70px;
                height: 70px;
                font-size: 24px;
            }

            .settings-modal-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .profile-modal-body {
                padding: 25px 20px;
            }

            .settings-modal-body {
                padding: 15px 20px 20px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 18px;
                padding-bottom: 15px;
            }

            .info-label {
                width: 100%;
                font-size: 0.9em;
            }

            .info-value {
                width: 100%;
                padding-left: 0;
                font-size: 0.92em;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-item label {
                margin-bottom: 4px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .mobile-menu-content {
                width: 100%;
            }

            .header-content {
                padding: 0 12px;
                min-height: 60px;
            }

            .logo-image {
                width: 120px;
                height: 40px;
            }

            .mobile-user-avatar {
                width: 35px;
                height: 35px;
            }

            .mobile-menu-btn {
                width: 35px;
                height: 35px;
                font-size: 1.1em;
            }
            
            .mobile-menu-header {
                padding: 15px;
            }
            
            .mobile-menu-actions {
                padding: 15px 0;
            }
            
            .mobile-menu-item {
                padding: 12px 15px;
            }

            .profile-modal-header-text h2 {
                font-size: 20px;
            }
            
            .profile-modal-header-text p {
                font-size: 14px;
            }
            
            .info-label, .info-value {
                font-size: 0.9em;
            }
        }

        .content-area {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background: var(--panel-bg);
            color: var(--text-primary);
        }
    </style>
</head>
<body class="<?php echo $darkModeActive ? 'dark-mode' : ''; ?>">
    <header class="top-header">
        <div class="header-content">
<div class="header-left">
    <div class="logo-placeholder" onclick="goToDashboard()">
        <div class="logo-image">
            <div class="logo-icon">
                <img src="../../public/assets/images/project.png" alt="logo" class="logo-img">
            </div>
        </div>
    </div>
</div>
            
            <div class="header-right desktop-view">
                <div class="user-welcome-container" 
                     onmouseenter="showDropdown()" 
                     onmouseleave="hideDropdown()">
                    <div class="welcome-text" onclick="toggleDropdown()">
                        Welcome, <?php echo htmlspecialchars($this->user['firstName'] . ' ' . $this->user['lastName']); ?>
                    </div>

                    <div class="welcome-dropdown">
                        <a class="dropdown-item" href="#" onclick="toggleDarkMode(); return false; hideDropdown();">
                            <span class="dropdown-icon"><?php echo $darkModeActive ? '☀️' : '🌙'; ?></span>
                            <span class="dropdown-text"><?php echo $darkModeActive ? 'Light Mode' : 'Dark Mode'; ?></span>
                        </a>
                        
                        <a class="dropdown-item" href="#" onclick="showSettings(); return false;">
                            <span class="dropdown-icon">⚙️</span>
                            <span class="dropdown-text">Settings</span>
                        </a>
                        
                        <a class="dropdown-item logout-item" href="/logout" onclick="hideDropdown();">
                            <span class="dropdown-icon">➡️</span>
                            <span class="dropdown-text">Logout</span>
                        </a>
                    </div>
                </div>

                <div class="user-avatar" onclick="showProfile()">
                    <?php echo $userInitials; ?>
                </div>
            </div>

            <div class="mobile-view">
                <div class="mobile-header">
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                        <span class="menu-icon">☰</span>
                    </button>
                    <div class="mobile-user-avatar" onclick="showProfile()">
                        <?php echo $userInitials; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileMenuOverlay" class="mobile-menu-overlay">
            <div class="mobile-menu-content">
                <div class="mobile-menu-header">
                    <div class="mobile-user-info">
                        <div class="mobile-user-avatar-large"><?php echo $userInitials; ?></div>
                        <div class="mobile-user-details">
                            <div class="mobile-user-name"><?php echo htmlspecialchars($this->user['firstName'] . ' ' . $this->user['lastName']); ?></div>
                            <div class="mobile-user-role"><?php echo htmlspecialchars($this->user['position'] ?? 'User'); ?></div>
                        </div>
                    </div>
                    <button class="close-mobile-menu" onclick="closeMobileMenu()">×</button>
                </div>

                <div class="mobile-nav-section">
                    <h3 class="mobile-nav-title">Navigation</h3>

                    <a class="mobile-menu-item" href="#" onclick="showSettings(); closeMobileMenu(); return false;">
                        <span class="mobile-menu-icon">⚙️</span>
                        <span class="mobile-menu-text">Settings</span>
                    </a>
                </div>

                <div class="mobile-menu-actions">
                    <a class="mobile-menu-item" href="#" onclick="toggleDarkMode(); return false;">
                        <span class="mobile-menu-icon"><?php echo $darkModeActive ? '☀️' : '🌙'; ?></span>
                        <span class="mobile-menu-text"><?php echo $darkModeActive ? 'Light Mode' : 'Dark Mode'; ?></span>
                    </a>
                    
                    <a class="mobile-menu-item logout-mobile" href="/logout">
                        <span class="mobile-menu-icon">➡️</span>
                        <span class="mobile-menu-text">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Profile Modal -->
    <div id="profileModal" class="profile-modal-overlay">
        <div class="profile-modal-content">
            <button class="profile-close-btn" onclick="closeProfileModal()">×</button>
            <div class="profile-modal-header">
                <div class="profile-modal-avatar"><?php echo $userInitials; ?></div>
                <div class="profile-modal-header-text">
                    <h2>My Details</h2>
                    <p><?php echo htmlspecialchars($this->user['position'] ?? 'Employee'); ?></p>
                </div>
            </div>
            <div class="profile-modal-body">
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['firstName'] ?? ''); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Surname:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['lastName'] ?? ''); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['email'] ?? 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cell:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['contactNo'] ?? 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Department:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['department'] ?? 'Administration'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Position:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['position'] ?? 'Employee'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Employee ID:</div>
                    <div class="info-value"><?php echo htmlspecialchars($this->user['employeeId'] ?? 'Not provided'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="settings-modal-overlay">
        <div class="settings-modal-content">
            <button class="settings-close-btn" onclick="closeSettingsModal()">×</button>
            <div class="settings-modal-header">
                <div class="settings-modal-avatar"><?php echo $userInitials; ?></div>
                <div class="settings-modal-header-text">
                    <h2>Account Settings</h2>
                    <p>Update your password</p>
                </div>
            </div>
            <div class="settings-modal-body">
                <form onsubmit="saveSettings(event)">
                    <div class="user-info-section">
                        <h3>Personal Information</h3>
                        <div class="read-only-info">
                            <div class="info-item">
                                <label>First Name</label>
                                <div class="info-value"><?php echo htmlspecialchars($this->user['firstName'] ?? ''); ?></div>
                            </div>
                            <div class="info-item">
                                <label>Last Name</label>
                                <div class="info-value"><?php echo htmlspecialchars($this->user['lastName'] ?? ''); ?></div>
                            </div>
                            <div class="info-item">
                                <label>Email Address</label>
                                <div class="info-value"><?php echo htmlspecialchars($this->user['email'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="info-item">
                                <label>Contact Number</label>
                                <div class="info-value"><?php echo htmlspecialchars($this->user['contactNo'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Password change section -->
                    <div class="password-section">
                        <h3>Change Password</h3>
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <div class="password-input-container">
                                <input 
                                    type="password" 
                                    id="currentPassword" 
                                    required
                                    placeholder="Enter your current password"
                                >
                                <button 
                                    type="button" 
                                    class="password-toggle"
                                    onclick="togglePasswordVisibility('current')"
                                >
                                    <span>👁️‍🗨️</span>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <div class="password-input-container">
                                <input 
                                    type="password" 
                                    id="newPassword" 
                                    required
                                    placeholder="Enter new password"
                                    minlength="6"
                                >
                                <button 
                                    type="button" 
                                    class="password-toggle"
                                    onclick="togglePasswordVisibility('new')"
                                >
                                    <span>👁️‍🗨️</span>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <div class="password-input-container">
                                <input 
                                    type="password" 
                                    id="confirmPassword" 
                                    required
                                    placeholder="Confirm new password"
                                >
                                <button 
                                    type="button" 
                                    class="password-toggle"
                                    onclick="togglePasswordVisibility('confirm')"
                                >
                                    <span>👁️‍🗨️</span>
                                </button>
                            </div>
                            <div id="passwordError" class="error-message" style="display: none;">
                                Passwords do not match
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeSettingsModal()">Cancel</button>
                        <button 
                            type="submit" 
                            class="btn-primary"
                            id="savePasswordBtn"
                        >
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        // Dropdown functionality
        let dropdownTimeout;

        function showDropdown() {
            const dropdown = document.querySelector('.welcome-dropdown');
            dropdown.classList.add('show');
            clearTimeout(dropdownTimeout);
        }

        function hideDropdown() {
            const dropdown = document.querySelector('.welcome-dropdown');
            dropdownTimeout = setTimeout(() => {
                dropdown.classList.remove('show');
            }, 200); // Small delay to allow clicking
        }

        function toggleDropdown() {
            const dropdown = document.querySelector('.welcome-dropdown');
            if (dropdown.classList.contains('show')) {
                hideDropdown();
            } else {
                showDropdown();
            }
        }

        // Profile Modal functionality
        function showProfile() {
            const modal = document.getElementById('profileModal');
            modal.style.display = 'flex';
            // Close other overlays
            closeMobileMenu();
            hideDropdown();
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            modal.style.display = 'none';
        }

        // Settings Modal functionality
        function showSettings() {
            const modal = document.getElementById('settingsModal');
            modal.style.display = 'flex';
            // Close other overlays
            closeMobileMenu();
            hideDropdown();
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settingsModal');
            modal.style.display = 'none';
            // Reset form
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
        }

        // Password visibility toggle
        function togglePasswordVisibility(field) {
            const input = document.getElementById(field + 'Password');
            const button = input.parentNode.querySelector('.password-toggle');
            const span = button.querySelector('span');
            
            if (input.type === 'password') {
                input.type = 'text';
                span.textContent = '👁️';
            } else {
                input.type = 'password';
                span.textContent = '👁️‍🗨️';
            }
        }

        // Password validation
        function validatePasswords() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorElement = document.getElementById('passwordError');
            const saveButton = document.getElementById('savePasswordBtn');
            
            if (confirmPassword && newPassword !== confirmPassword) {
                errorElement.style.display = 'block';
                saveButton.disabled = true;
                return false;
            } else {
                errorElement.style.display = 'none';
                saveButton.disabled = !newPassword || !confirmPassword || newPassword.length < 6;
                return true;
            }
        }

        // Save settings
        function saveSettings(event) {
            event.preventDefault();
            
            if (!validatePasswords()) {
                return;
            }
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            
            // Here you would typically make an AJAX call to save the password
            alert('Password change functionality would be implemented here.\n\nCurrent Password: ' + currentPassword + '\nNew Password: ' + newPassword);
            
            // Close modal after "saving"
            closeSettingsModal();
        }

        // Real-time password validation
        document.getElementById('newPassword').addEventListener('input', validatePasswords);
        document.getElementById('confirmPassword').addEventListener('input', validatePasswords);

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.welcome-dropdown');
            const welcomeContainer = document.querySelector('.user-welcome-container');
            
            if (dropdown && welcomeContainer && !welcomeContainer.contains(event.target)) {
                hideDropdown();
            }
        });

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const profileModal = document.getElementById('profileModal');
            const settingsModal = document.getElementById('settingsModal');
            const mobileOverlay = document.getElementById('mobileMenuOverlay');
            
            if (profileModal.style.display === 'flex' && event.target === profileModal) {
                closeProfileModal();
            }
            
            if (settingsModal.style.display === 'flex' && event.target === settingsModal) {
                closeSettingsModal();
            }
            
            if (mobileOverlay.style.display === 'flex' && !event.target.closest('.mobile-menu-content')) {
                closeMobileMenu();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeProfileModal();
                closeSettingsModal();
                closeMobileMenu();
                hideDropdown();
            }
        });

        function goToDashboard() {
            window.location.href = '/dashboard';
        }
        
        function goToClockIn() {
            window.location.href = '/clock-in';
        }
        
        function toggleDarkMode() {
            // Set a cookie to remember the preference
            const isDarkMode = document.body.classList.contains('dark-mode');
            const newDarkMode = !isDarkMode;
            
            // Set cookie for 1 year
            document.cookie = `dark_mode=${newDarkMode}; path=/; max-age=${365 * 24 * 60 * 60}`;
            
            // Apply the theme immediately
            if (newDarkMode) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
            
            // Update the UI text
            updateDarkModeText();
        }
        
        function updateDarkModeText() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const darkModeTexts = document.querySelectorAll('.dropdown-text, .mobile-menu-text');
            const darkModeIcons = document.querySelectorAll('.dropdown-icon, .mobile-menu-icon');
            
            darkModeTexts.forEach(text => {
                if (text.textContent.includes('Light Mode') || text.textContent.includes('Dark Mode')) {
                    text.textContent = isDarkMode ? 'Light Mode' : 'Dark Mode';
                }
            });
            
            darkModeIcons.forEach(icon => {
                if (icon.textContent === '☀️' || icon.textContent === '🌙') {
                    icon.textContent = isDarkMode ? '☀️' : '🌙';
                }
            });
        }
        
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobileMenuOverlay');
            overlay.style.display = overlay.style.display === 'flex' ? 'none' : 'flex';
        }
        
        function closeMobileMenu() {
            document.getElementById('mobileMenuOverlay').style.display = 'none';
        }

        // Initialize dark mode text on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDarkModeText();
        });
    </script>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    private function isDarkModeActive() {
        return $this->isDarkMode;
    }
    
    private function getUserInitials() {
        $firstName = $this->user['firstName'] ?? '';
        $lastName = $this->user['lastName'] ?? '';
        return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    }
}

// =============================================================================
// USAGE EXAMPLE - This is what makes it display!
// =============================================================================

// Sample user data with all profile fields
$user = [
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'john.doe@company.com',
    'contactNo' => '+1 (555) 123-4567',
    'department' => 'Engineering',
    'position' => 'Senior Developer',
    'employeeId' => 'EMP-2024-001'
];

// Check if dark mode is enabled (using cookies)
$isDarkMode = isset($_COOKIE['dark_mode']) ? $_COOKIE['dark_mode'] === 'true' : false;

// Get current path
$currentPath = $_SERVER['REQUEST_URI'] ?? '/dashboard';

// Create and render the header
$header = new TopHeader($user, [], $isDarkMode, $currentPath);
echo $header->render();
?>
