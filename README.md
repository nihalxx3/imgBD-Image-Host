# BDIX ImageHost

A fast, secure, and user-friendly PHP image host with instant uploads, NSFW detection, and robust metadata stripping. Designed for BDIX/CDN deployment, with strong access controls and daily cleanup automation.

Currently hosted at: [https](https://timepass.fpureit.top/) 



## Features
- **Instant Image Uploads**: Drag & drop or select images (PNG, JPEG, GIF) up to 10MB.
- **NSFW Detection**: Python-powered batch scan using NudeNet; adult images flagged and deleted automatically.
- **Metadata Stripping**: Lossless removal of all EXIF/XMP metadata (including iPhone photos) using `exiftool`.
- **Rate Limiting & reCAPTCHA**: Prevents abuse with per-IP limits and optional Google reCAPTCHA.
- **Auto-Delete**: Images and DB records older than 7 days are purged daily.
- **Access Control**: `.htaccess` rules block sensitive files, restrict directory listing, and secure admin/phpMyAdmin.
- **Dark Mode UI**: Modern Bootstrap interface with theme toggle and background pattern.
- **Admin & Signup**: Simple user system with admin login and signup page.

## Folder Structure
```
├── index.php           # Main upload logic
├── view.php            # Image viewer by hexcode
├── admin.php           # Admin login
├── signup.php          # User signup
├── config.php          # Site configuration
├── connection.php      # DB connection & user table
├── main.css            # Custom dark mode theme
├── uploads/            # Uploaded images
│   └── nsfw/           # NSFW images (auto-deleted)
├── nsfw/               # Python scripts, logs, batch tools
│   ├── nsfwdb.py       # NSFW detection (NudeNet)
│   ├── step1.py        # Batch workflow runner
│   ├── move_nsfw.py    # Moves flagged images
│   └── ...             # Logs, whitelist, etc.
├── .htaccess           # Apache access rules (see below)
├── favicon.ico         # Site icon
├── bg.png, dark-bg.png # Background patterns
```

## Setup & Usage
1. **Clone the repo**
   ```sh
   git clone https://github.com/yourusername/bdix-imagehost.git
   cd bdix-imagehost
   ```
2. **Install dependencies**
   - PHP 7.4+
   - MySQL/MariaDB
   - Apache (mod_rewrite enabled)
   - Python 3.8+ (`pip install nudenet opencv-python`)
   - `exiftool` (for metadata stripping)
3. **Configure**
   - Edit `config.php` with your DB credentials and reCAPTCHA keys.
   - Set proper permissions for `uploads/` and `nsfw/` folders.
4. **Run**
   - Deploy on XAMPP, cPanel, or bare VM.
   - Access `/` for uploads, `/admin.php` for admin login.
   - NSFW batch scan: run `python nsfw/step1.py` as needed.

## Security Notes
- **.htaccess**: Sensitive files (Python, logs, configs) are blocked. Directory listing is disabled. Only `index.php` is allowed in `uploads/nsfw/`.
- **phpMyAdmin**: Restrict access via `.htaccess` or firewall.
- **Git Ignore**: Add `.htaccess` to `.gitignore` to avoid leaking access rules.

## .gitignore Example
```
.htaccess
uploads/
nsfw/*.log
cleanup.lock
```

## License
MIT

---
**Author:** [nihalxx3](https://github.com/nihalxx3)

For support, open an issue or contact via Discord: nihalxx3
