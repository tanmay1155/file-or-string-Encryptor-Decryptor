<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encryption/Decryption</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            margin-top: 50px;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        select,
        input[type="text"],
        input[type="password"],
        input[type="file"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            background: url('data:image/svg+xml;utf8,<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>') no-repeat right 10px center/12px;
        }

        input[type="file"] {
            cursor: pointer;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        pre {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Encryption/Decryption</h1>
    <form id="encryptionForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <label for="sourceType">Choose Source:</label><br>
        <select id="sourceType" name="sourceType" onchange="toggleInputFields()" required>
            <option value="string">String</option>
            <option value="file">File</option>
        </select><br>

        <div id="stringInput">
            <label for="inputString">Input String:</label><br>
            <input type="text" id="inputString" name="inputString"><br>
        </div>

        <div id="fileInput" style="display:none;">
            <label for="file">Upload File:</label><br>
            <input type="file" id="file" name="file"><br>
        </div>

        <label for="operation">Operation:</label><br>
        <select id="operation" name="operation" required>
            <option value="encrypt">Encrypt</option>
            <option value="decrypt">Decrypt</option>
        </select><br>

        <label for="encryptionType">Algorithm:</label><br>
        <select id="encryptionType" name="encryptionType" required>
            <option value="aes-256-cbc">AES-256-CBC</option>
            <option value="aes-256-ecb">AES-256-ECB</option>
            <option value="aes-128-cbc">AES-128-CBC</option>
            <option value="aes-128-ecb">AES-128-ECB</option>
            <option value="des-ede3-cbc">DES-CBC</option>
            <option value="bf-cbc">BlowFish-CBC</option>
            <!-- Add more encryption algorithms as needed -->
        </select><br>

        <label for="key">Key:</label><br>
        <input type="text" id="key" name="key" required><br>

        <label for="iv">IV/Nonce:</label><br>
        <input type="text" id="iv" name="iv" required><br>

        <input type="submit" value="Submit">
    </form>

    <script>
        function toggleInputFields() {
            var sourceType = document.getElementById("sourceType").value;
            if (sourceType === "string") {
                document.getElementById("stringInput").style.display = "block";
                document.getElementById("fileInput").style.display = "none";
            } else if (sourceType === "file") {
                document.getElementById("stringInput").style.display = "none";
                document.getElementById("fileInput").style.display = "block";
            }
        }
    </script>

<?php
function encryptData($data, $key, $iv, $method) {
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted);
}

function decryptData($encryptedData, $key, $iv, $method ) {
    $decrypted = openssl_decrypt(base64_decode($encryptedData), $method, $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}    

function handleFormSubmission() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $sourceType = $_POST["sourceType"];
        $operation = $_POST["operation"];
        $encryptionType = $_POST["encryptionType"];
        $key = ($_POST["key"]); // Decode the base64 encoded key
        $iv = ($_POST["iv"]);   // Decode the base64 encoded IV
        $formData = "";

        // Define minimum sizes for key and IV based on encryption algorithm
        $minKeySize = openssl_cipher_key_length($encryptionType);
        $minIVSize = openssl_cipher_iv_length($encryptionType);

        // Validate key and IV sizes
        if (strlen($key) != $minKeySize || strlen($iv) != $minIVSize) {
            echo "<script>alert('Key and IV size must be at least $minKeySize and $minIVSize bytes respectively.')</script>";
            return;
        }

        // Handle input data based on source type
        if ($sourceType == "string") {
            $inputData = $operation === "encrypt" ? $_POST["inputString"] : $_POST["inputString"];
        } elseif ($sourceType == "file") {
            // Retrieve the file content
            $fileData = file_get_contents($_FILES["file"]["tmp_name"]);
            if ($fileData === false) {
                echo "Failed to read the file.";
                return;
            }
            $inputData = $operation === "encrypt" ? $fileData : $fileData;

            // Define the output file path
            $outputDirectory = __DIR__ . "/"; // Change this to the desired output directory
            $outputFileName = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
            $outputFileExtension = $operation === "encrypt" ? "_enc.txt" : "_dec." . pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $outputFilePath = $outputDirectory . $outputFileName . $outputFileExtension;

            // Perform encryption or decryption
            if ($operation === "encrypt") {
                $encryptedData = encryptData($inputData, $key, $iv, $encryptionType);
                file_put_contents($outputFilePath, $encryptedData);
                $formData .= "Encryption successful. Encrypted file saved as: " . $outputFilePath;
            } elseif ($operation === "decrypt") {
                $decryptedData = decryptData($inputData, $key, $iv, $encryptionType);
                file_put_contents($outputFilePath, $decryptedData);
                $formData .= "Decryption successful. Decrypted file saved as: " . $outputFilePath;
            }

            // Output the form data on the page
            echo "<pre>" . htmlspecialchars($formData) . "</pre>";
            return;
        }

        // For string input, continue with encryption/decryption as before
        if ($operation === "encrypt") {
            $encryptedData = encryptData($inputData, $key, $iv, $encryptionType);
            $formData .= "Encrypted Data: " . $encryptedData . "\n";
        } elseif ($operation === "decrypt") {
            $decryptedData = decryptData($inputData, $key, $iv, $encryptionType);
            $formData .= "Decrypted Data: " . $decryptedData . "\n";
        }
        $formData .= "Operation: " . $operation . "\n";
        $formData .= "Encryption Algorithm: " . $encryptionType . "\n";
        $formData .= "Key: " . ($key) . "\n"; 
        $formData .= "IV/Nonce: " . ($iv) . "\n\n";
        echo "<pre>" . htmlspecialchars($formData) . "</pre>";
    }
}
handleFormSubmission();
?>

    
</body>
</html>
