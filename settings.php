<?php
session_start();

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Store selected language in session
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}

include 'header.php';

// Array of world languages
$languages = array(
    'en' => 'English',
    'hi' => 'हिंदी (Hindi)', 
    'es' => 'Español (Spanish)',
    'fr' => 'Français (French)',
    'de' => 'Deutsch (German)',
    'it' => 'Italiano (Italian)',
    'pt' => 'Português (Portuguese)',
    'ru' => 'Русский (Russian)',
    'ja' => '日本語 (Japanese)',
    'ko' => '한국어 (Korean)',
    'zh' => '中文 (Chinese)',
    'ar' => 'العربية (Arabic)',
    'tr' => 'Türkçe (Turkish)',
    'vi' => 'Tiếng Việt (Vietnamese)',
    'th' => 'ไทย (Thai)'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Yards of Grace</title>
    <style>
        .settings-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: purple;
            margin-bottom: 20px;
            text-align: center;
        }

        .language-section {
            margin: 30px 0;
            text-align: center;
        }

        .language-select {
            width: 300px;
            padding: 12px;
            border: 2px solid purple;
            border-radius: 4px;
            font-size: 16px;
            margin: 20px auto;
            display: block;
            cursor: pointer;
        }

        .language-select:focus {
            outline: none;
            border-color: #800080;
            box-shadow: 0 0 5px rgba(128,0,128,0.3);
        }

        .current-language {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
            color: purple;
            font-weight: bold;
        }

        .info-box {
            margin-top: 30px;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 8px;
            text-align: center;
        }

        .info-box h3 {
            color: purple;
            margin-bottom: 15px;
        }

        .info-box p {
            color: #666;
            line-height: 1.6;
            margin: 10px 0;
        }

        .translate-widget {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        #google_translate_element {
            margin-top: 20px;
        }

        .goog-te-gadget {
            font-family: Arial, sans-serif !important;
        }

        .goog-te-gadget-simple {
            background-color: purple !important;
            border: none !important;
            padding: 8px !important;
            border-radius: 4px !important;
            color: white !important;
        }

        .goog-te-gadget-simple span {
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h1>Language Settings</h1>
        
        <div class="language-section">
            <div class="current-language">
                Current Language: <?php echo $languages[$_SESSION['language'] ?? 'en']; ?>
            </div>
            
            <div id="google_translate_element"></div>
        </div>

        <div class="info-box">
            <h3>🌐 Website Translation Available!</h3>
            <p>Use the Google Translate dropdown above to translate this website into any language!</p>
            <p>Simply select your preferred language from the dropdown menu.</p>
        </div>
    </div>

    <!-- Google Translate Script -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html><?php include 'footer.php'; ?>

