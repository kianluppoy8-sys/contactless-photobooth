# Contactless Photobooth

Welcome to the **Contactless Photobooth** project! This is a web-based, interactive photobooth application that allows users to seamlessly capture, process, and download their photos without needing to touch a shared screen, using QR code technology.

## 🚀 Features
- **QR Code Scanning**: Uses the `html5-qrcode` library for seamless touchless photo sessions.
- **Photo Capture & Merging**: Capture multiple photos and automatically merge them into a single photobooth strip or collage (handled via PHP processing).
- **Session Management**: Each user gets a unique, secure session for their photos.
- **Admin Panel & Login Area**: Secure login system with an admin view for monitoring QR codes and sessions.
- **Responsive Design**: Modern and clean user interface, suitable for various device sizes.

## 🛠️ Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Libraries**:
  - [html5-qrcode](https://github.com/mebjas/html5-qrcode) (Client-side QR reading)
  - PHP QR Code (Generating dynamic QR tokens)

## 📋 Prerequisites
To run this application locally, you will need a local development environment that supports PHP.
- **XAMPP / LAMP / WAMP Stack** installed on your system.
- PHP version 7.4 or newer (with GD extension enabled for image merging).

## ⚙️ Installation & Setup
1. **Clone the repository** (if you haven't already):
   ```bash
   git clone https://github.com/kianluppoy8-sys/contactless-photobooth.git
   ```
2. **Move files to your server directory**:
   - For XAMPP: Move the folder into `C:\xampp\htdocs\` or `/opt/lampp/htdocs/`.
   - Make sure your folder is named `photoboth` or `contactless-photobooth`.
3. **Configure the Project**:
   - Open `config.php` (if applicable) and adjust any necessary settings or database connections.
4. **Run the Project**:
   - Start your Apache server (via XAMPP).
   - Open your web browser and navigate to: `http://localhost/photoboth/`

## 📸 How to Use
1. A user scans the generated QR code on the main display to initiate their session.
2. The photobooth allows the user to align and capture their snapshots.
3. The server automatically combines the snapped photos using the defined layout background.
4. The user receives a finalized output that they can easily download to their own device.

---
*Created by [kianluppoy8-sys](https://github.com/kianluppoy8-sys)*
