import re

with open('admin/settings.php', 'r') as f:
    content = f.read()

replacement = """    if (!in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException("Invalid file type for $fileInputName. Only JPG, PNG, GIF, WebP allowed.");
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];
    $ext = $extensions[$mime] ?? 'png'; // Fallback just in case, though handled by in_array

    $filename = $fileInputName . '_' . time() . '.' . $ext;"""

old_code = """    if (!in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException("Invalid file type for $fileInputName. Only JPG, PNG, GIF, WebP allowed.");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $fileInputName . '_' . time() . '.' . $ext;"""

new_content = content.replace(old_code, replacement)

with open('admin/settings.php', 'w') as f:
    f.write(new_content)

print("Fixed RCE in admin/settings.php")
