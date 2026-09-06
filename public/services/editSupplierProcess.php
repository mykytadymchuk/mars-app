<?php

session_start();

require_once __DIR__ . '/../../services/databaseConnect.php';

use MongoDB\BSON\ObjectId;

$suppliersId = $_POST["id"] ?? null;
$suppliersName = trim($_POST["suppliersName"] ?? "");
$suppliersPhone = trim($_POST["suppliersPhone"] ?? "");

if (!$suppliersId) {
    header("Location: ../suppliers.php?error=unknown_error");
    exit;
}

try {
    $supplierObjectId = new ObjectId($suppliersId);
} catch (Exception $e) {
    header("Location: ../suppliers.php?error=unknown_error");
    exit;
}

if ($suppliersName === "") {
    header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=empty_suppliers_name");
    exit;
}

if (!preg_match('/^\+380\d{9}$/', $suppliersPhone)) {
    header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=incorrect_phone_number_format");
    exit;
}

try {

    // Find supplier
    $supplier = $suppliersCollection->findOne([
        "_id" => $supplierObjectId
    ]);

    if (!$supplier) {
        header("Location: ../suppliers.php?error=unknown_error");
        exit;
    }

    // Check whether another supplier already uses this phone number
    $existingSupplier = $suppliersCollection->findOne([
        "Номер телефону" => $suppliersPhone,
        "_id" => [
            '$ne' => $supplierObjectId
        ]
    ]);

    if ($existingSupplier) {
        header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=supplier_exists");
        exit;
    }

    /*
     * IMAGE UPLOAD
     */

    $photoFileName = $supplier["Фото"] ?? null;

    if (
        isset($_FILES["supplierPhoto"]) &&
        $_FILES["supplierPhoto"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        // Check for upload errors
        if (
            $_FILES["supplierPhoto"]["error"] === UPLOAD_ERR_INI_SIZE ||
            $_FILES["supplierPhoto"]["error"] === UPLOAD_ERR_FORM_SIZE
        ) {
            header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=too_large_file");
            exit;
        }

        if ($_FILES["supplierPhoto"]["error"] !== UPLOAD_ERR_OK) {
            header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=unknown_error");
            exit;
        }

        // Maximum file size: 10 MB
        if ($_FILES["supplierPhoto"]["size"] > 15 * 1024 * 1024) {
            header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=too_large_file");
            exit;
        }

        // Check MIME type
        $allowedMimeTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
            "image/svg+xml" => "svg"
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mimeType = $finfo->file(
            $_FILES["supplierPhoto"]["tmp_name"]
        );

        if (!isset($allowedMimeTypes[$mimeType])) {
            header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=unknown_error");
            exit;
        }

        $extension = $allowedMimeTypes[$mimeType];

        /*
         * Generate a unique filename.
         * Using the supplier ID prevents filename collisions.
         */
        $photoFileName = $suppliersId . "." . $extension;

        // Directory where supplier photos will be stored
        $uploadDirectory = __DIR__ . "/../src/suppliers/";

        // Create directory if it doesn't exist
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $photoPath = $uploadDirectory . $photoFileName;

        // Delete old photo if extension changed
        if (!empty($supplier["Фото"])) {

            $oldPhotoPath = $uploadDirectory . $supplier["Фото"];

            if (
                file_exists($oldPhotoPath) &&
                $oldPhotoPath !== $photoPath
            ) {
                unlink($oldPhotoPath);
            }
        }

        // Move uploaded file
        if (
            !move_uploaded_file(
                $_FILES["supplierPhoto"]["tmp_name"],
                $photoPath
            )
        ) {
            header("Location: ../editSupplier.php?id=" . urlencode($suppliersId) . "&error=unknown_error");
            exit;
        }
    }

    $customFieldNames = $_POST["customFieldName"] ?? [];
    $customFieldValues = $_POST["customFieldValue"] ?? [];

    $customFields = [];

    foreach ($customFieldNames as $index => $fieldName) {

        $fieldName = trim($fieldName);
        $fieldValue = trim($customFieldValues[$index] ?? "");

        if ($fieldName === "") {
            continue;
        }

        if ($fieldValue === "") {
            continue;
        }

        // Don't allow users to overwrite system fields
        if (
            $fieldName === "_id" ||
            $fieldName === "Постачальник" ||
            $fieldName === "Номер телефону" ||
            $fieldName === "Фото" ||
            $fieldName === "Додаткові поля" ||
            str_starts_with($fieldName, "$") ||
            str_contains($fieldName, ".") ||
            str_contains($fieldName, "\0")
        ) {
            continue;
        }

        $customFields[$fieldName] = $fieldValue;
    }

    $updateData = [
        "Постачальник" => $suppliersName,
        "Номер телефону" => $suppliersPhone
    ];

    // Store each additional field directly in the supplier document.
    foreach ($customFields as $fieldName => $fieldValue) {
        $updateData[$fieldName] = $fieldValue;
    }

    // Remove the legacy nested object and any deleted top-level custom fields.
    $systemFieldNames = ["_id", "Постачальник", "Номер телефону", "Фото", "Додаткові поля"];
    $unsetData = ["Додаткові поля" => ""];

    foreach ($supplier as $fieldName => $fieldValue) {
        if (!in_array($fieldName, $systemFieldNames, true) && !array_key_exists($fieldName, $customFields)) {
            $unsetData[$fieldName] = "";
        }
    }

    // Only add "Фото" if a photo exists
    if ($photoFileName !== null) {
        $updateData["Фото"] = $photoFileName;
    }

    $result = $suppliersCollection->updateOne(
        [
            "_id" => $supplierObjectId
        ],
        [
            '$set' => $updateData,
            '$unset' => $unsetData
        ]
    );

    if (
        $result->getModifiedCount() > 0 ||
        $result->getMatchedCount() > 0
    ) {
        header("Location: ../suppliers.php");
        exit;
    }

    header(
        "Location: ../editSupplier.php?id=" .
        urlencode($suppliersId) .
        "&error=unknown_error"
    );

    exit;

} catch (Exception $e) {

    header(
        "Location: ../editSupplier.php?id=" .
        urlencode($suppliersId) .
        "&error=unknown_error"
    );

    exit;
}
