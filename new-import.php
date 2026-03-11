<?php
declare(strict_types=1);

// global variables.
//
$message = '';
$messageType = '';
$output = '';
$version = "1.0";
$mysqliConfig = array();

// include configuration file.
//
$configFile = $_SERVER['DOCUMENT_ROOT']."/config.php";
if (!isset($documentRoot)) $documentRoot = $_SERVER['DOCUMENT_ROOT']."";

// in configuration file exists, include it, otherwise exit script.
//
if (!file_exists($configFile)) { 
	echo "Could not load configuration $configFile<br>"; 
	ob_flush();
	die();
}
else{ 
	include($configFile); 
}

// include required class for mysql .
//
$mysqliClass = $documentRoot.'/mysqli.class.php';
if (!file_exists($mysqliClass)) { 
	echo "Could not load configuration $mysqliClass<br>"; 
	ob_flush();
	die();
}
else{ 
	include($mysqliClass); 
}

// include required importer.
//
$importClass = $documentRoot.'/dzs_import.php';
if (!file_exists($importClass)) { 
	echo "Could not load configuration $importClass<br>"; 
	ob_flush();
	die();
}
else{ 
	include($importClass); 
}

// create valid upload directory. The folder is configured in the
// configuration config.php.
//
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$pwd = "";
if (isset($_POST['upload_password'])){ $pwd = md5($_POST['upload_password']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (($pwd == "") || ($pwd != md5($CFG_importPwd))) {
        $message = 'Invalid password.';
        $messageType = 'error';
	} elseif (!isset($_FILES['csv_file'])) {
        $message = 'No file was uploaded.';
        $messageType = 'error';
    } elseif ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload failed with error code: ' . $_FILES['csv_file']['error'];
        $messageType = 'error';
    } else {
        $file = $_FILES['csv_file'];
        $originalName = $file['name'];
        $tmpName = $file['tmp_name'];

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            $message = 'Only CSV files are allowed.';
            $messageType = 'error';
        } else {
			
			$finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);

            $allowedMimeTypes = [
                'text/plain',
                'text/csv',
                'application/csv',
                'application/vnd.ms-excel',
                'text/comma-separated-values',
            ];

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                $message = 'Invalid file type. Only CSV files are allowed.';
                $messageType = 'error';
            } else {

				$safeFilename = uniqid('csv_', true) . '.csv';
                $destination = $uploadDir . '/' . date ("Ymd-His") . '-' . $safeFilename;
				
                if (!move_uploaded_file($tmpName, $destination)) {
                    $message = 'Failed to save uploaded file.';
                    $messageType = 'error';
                } else {
					
					// Include the MySQLi class, create an instance of the dzs_import class and call method
					// import with the path to the CSV file to import the data.
					//
					$dzsImport = new dzs_import($mysqliConnection);
					if($dzsImport->Import($destination))
					{
						// When the import is done, generate a small report.
						//
						$count = $dzsImport->countData();
						foreach($count as $key => $value)
						{
							if ($message!="") $message.=" - ";
							$message .= "".$key.": ".$value."";
						}
						$message = "Import gelukt ( ".$message." )";
						
						// remove imported file.
						//unlink($destination);
					}					
					
                }
				
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DZS Online - CSV Upload</title>
    <style>
        :root {
            --primary: #0f4c81;
            --primary-dark: #0b3558;
            --accent: #1f7ae0;
            --success-bg: #eaf7ee;
            --success-text: #1f6b3b;
            --error-bg: #fdecec;
            --error-text: #a12626;
            --border: #d9e2ec;
            --text: #1f2937;
            --muted: #6b7280;
            --bg: #f4f7fb;
            --card: #ffffff;
            --shadow: 0 18px 45px rgba(15, 76, 129, 0.10);
            --radius: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(135deg, rgba(15, 76, 129, 0.08), rgba(31, 122, 224, 0.05)),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .card {
            width: 100%;
            max-width: 760px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(15, 76, 129, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            padding: 32px 32px 28px;
            text-align: center;
        }

        .logo {
            max-width: 220px;
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 18px;
            background: rgba(255, 255, 255, 0.08);
            padding: 10px 14px;
            border-radius: 14px;
        }

        .card-header h1 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.2;
        }

        .card-header p {
            margin: 0;
            font-size: 15px;
            opacity: 0.95;
        }

        .card-body {
            padding: 32px;
        }

        .info-box {
            background: #f8fbff;
            border: 1px solid #d9e9f7;
            color: var(--muted);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #b9e3c7;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: #f3c0c0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .file-input-wrapper,
        .password-wrapper {
            border: 2px dashed #b8c7d9;
            border-radius: 14px;
            padding: 22px;
            background: #fbfdff;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .file-input-wrapper:hover,
        .password-wrapper:hover {
            border-color: var(--accent);
            background: #f7fbff;
        }

        input[type="file"],
        input[type="password"] {
            width: 100%;
            font-size: 14px;
            color: var(--text);
        }

        input[type="password"] {
            padding: 12px 14px;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            background: #fff;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(31, 122, 224, 0.10);
        }

        .form-actions {
            margin-top: 24px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            padding: 14px 22px;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(15, 76, 129, 0.18);
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(15, 76, 129, 0.22);
        }

        .btn:active {
            transform: translateY(0);
        }

        .output-section {
            margin-top: 28px;
            border-top: 1px solid var(--border);
            padding-top: 24px;
        }

        .output-section h2 {
            margin: 0 0 12px;
            font-size: 18px;
            color: var(--primary-dark);
        }

        pre {
            margin: 0;
            background: #0f172a;
            color: #e5eef9;
            padding: 18px;
            border-radius: 14px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .footer-note {
            margin-top: 22px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 640px) {
            .card-header,
            .card-body {
                padding: 24px 20px;
            }

            .card-header h1 {
                font-size: 24px;
            }

            .logo {
                max-width: 180px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="card-header">
                <img
                    src="https://dzsonline.limecreations.nl/wp-content/uploads/2026/01/logo_dzsonline2.png"
                    alt="DZS Online Logo"
                    class="logo"
                >
                <h1>CSV Upload & Processing</h1>
                <p>Securely upload a CSV file and run automated processing.</p>
            </div>
			<?php if ($message == ""){ ?>
            <div class="card-body">
                <div class="info-box">
                    Please upload a valid <strong>CSV</strong> file only. Once uploaded, the file will be passed to the server-side processing script.
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="form-group">
                        <label for="upload_password">Upload password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                name="upload_password"
                                id="upload_password"
                                required
                                placeholder="Enter password"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="csv_file">Select CSV file</label>
                        <div class="file-input-wrapper">
                            <input
                                type="file"
                                name="csv_file"
                                id="csv_file"
                                accept=".csv,text/csv"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Upload and Process File</button>
                    </div>
                </form>

                <?php if ($output !== ''): ?>
                    <div class="output-section">
                        <h2>Processing Output</h2>
                        <pre><?php echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8'); ?></pre>
                    </div>
                <?php endif; ?>

                <div class="footer-note">
                    DZS Online &mdash; CSV processing interface - Development by <a href="https://concera.software" target="NEW">Concera</a>
                </div>
            </div>
			<?php }else{ ?>
			<div class="card-body">
				<div class="info-box">
					<?php echo $message; ?>
				</div>
				<div class="info-box">
					<a href="/import/">Klik hier om nog een import uit te voeren</a>
				</div>
                <div class="footer-note">
                    DZS Online &mdash; CSV processing interface - Development by <a href="https://concera.software" target="NEW">Concera</a>
                </div>
			</div>
			<?php }	?>
        </div>
    </div>
</body>
</html>
