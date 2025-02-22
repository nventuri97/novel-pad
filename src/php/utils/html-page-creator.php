<?php
openlog('html-page-creator.php', LOG_PID | LOG_PERROR, LOG_LOCAL0);

function createHtmlNovelPage($title, $genre, $content){
    $htmlContent = "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <link rel='icon' type='image/png' href='../../images/favicon_book.png'>
                    <title>$title</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f4f4f4;
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                        }
                        .story-container {
                            background: white;
                            padding: 20px;
                            border-radius: 10px;
                            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
                            max-width: 800px;
                            width: 100%;
                        }
                        h1 {
                            font-family: 'Georgia', serif;
                            text-align: center;
                            color: #333;
                        }
                        .genre {
                            text-align: center;
                            font-weight: bold;
                            color: #555;
                            background: #e0e0e0;
                            padding: 5px 10px;
                            border-radius: 5px;
                            display: inline-block;
                        }
                        .story-content {
                            margin-top: 20px;
                            line-height: 1.6;
                            text-align: justify;
                        }
                        .back-button {
                            margin-top: 20px;
                            padding: 10px 20px;
                            background-color: #007BFF;
                            color: white;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                            font-size: 16px;
                            transition: background-color 0.3s;
                        }
                        .back-button:hover {
                            background-color: #0056b3;
                        }
                    </style>
                </head>
                <body>
                    <div class='story-container'>
                        <h1>$title</h1>
                        <p class='genre'>$genre</p>
                        <div class='story-content'>$content</div>
                    </div>
                    <button class='back-button'>Back to Dashboard</button>
                    <script src='../../js/back_button.js'></script>
                </body>
                </html>";
    return $htmlContent;
}
?>