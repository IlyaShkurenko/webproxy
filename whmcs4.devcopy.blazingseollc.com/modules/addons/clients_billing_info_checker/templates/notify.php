<html>
<head>
    <title>You don't have the required billing information!</title>
    <style>
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert {
            padding: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .text-center {
             text-align: center;
        }
    </style>
</head>
<body>
    <div class="alert alert-info text-center">
        You don't have the required profile information! We will need this information before proceeding. Please complete all details <a href="<?php echo $systemURL; ?>/clientarea.php?action=details" title="HERE">HERE</a>.
    </div>
</body>
</html>