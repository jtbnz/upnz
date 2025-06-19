# Vaughans Upholstery Website

A modern, responsive website for Vaughans Upholstery featuring a comprehensive gallery system with admin functionality.

## Features

### Public Features
- **Home Page**: Overview of services and company information
- **About Page**: Company history, values, and team information
- **Services Page**: Detailed information about upholstery services
- **Gallery Page**: Curated portfolio of completed projects
- **Examples Page**: Full gallery with login functionality
- **Contact Page**: Contact information and inquiry form

### Admin Features
- **Secure Login System**: Admin authentication with session management
- **Image Management**: Upload and delete images in the examples gallery
- **Admin Panel**: Dedicated interface for managing gallery content

## Admin Access

### Login Credentials
- **Username**: `admin`
- **Password**: `K7m9P2x8Q5w3N6z1`

### Admin Functionality
1. **Access Examples Page**: Navigate to `/examples.php`
2. **Login**: Click the "Login" button and enter admin credentials
3. **Admin Panel**: Once logged in, access the admin panel via the "Admin Panel" button
4. **Upload Images**: Drag and drop or select images to upload (JPG, PNG, GIF, WebP up to 5MB)
5. **Delete Images**: Click the delete button on any image in the admin panel
6. **Logout**: Use the logout button to end the admin session

## File Structure

```
vaughans-upholstery/
├── index.html              # Home page
├── about.html              # About page
├── services.html           # Services page
├── gallery.html            # Gallery page
├── contact.html            # Contact page
├── examples.php            # Examples gallery with login
├── admin.php               # Admin panel
├── auth.php                # Authentication logic
├── config/
│   └── secrets.php         # User credentials (not in git)
├── css/
│   ├── normalize.css       # CSS reset
│   ├── styles.css          # Main styles
│   ├── examples.css        # Examples page styles
│   └── admin.css           # Admin panel styles
├── js/
│   ├── main.js             # Main JavaScript
│   ├── examples.js         # Examples page functionality
│   └── admin.js            # Admin panel functionality
├── images/
│   ├── gallery/            # Gallery images
│   ├── examples/           # Admin-managed examples
│   ├── hero-images/        # Hero section images
│   └── services/           # Service images
└── .gitignore              # Git ignore file
```

## Security Features

- **Session Management**: Secure PHP sessions with timeout
- **File Validation**: Server-side validation of uploaded files
- **Directory Protection**: Secrets file excluded from version control
- **Input Sanitization**: All user inputs are sanitized
- **File Type Restrictions**: Only image files allowed for upload

## Technical Requirements

- **Web Server**: Apache or Nginx with PHP support
- **PHP Version**: 7.4 or higher
- **Extensions**: Standard PHP extensions (no special requirements)
- **File Permissions**: Write permissions for `images/examples/` directory

## Installation

1. Upload all files to your web server
2. Ensure the `images/examples/` directory has write permissions
3. Configure your web server to serve PHP files
4. Access the website via your domain

## Development Notes

- The website uses modern CSS Grid and Flexbox for responsive layouts
- JavaScript is minimal and focuses on essential functionality
- PHP sessions handle authentication state
- File uploads are processed server-side with validation
- All navigation menus are automatically updated across pages

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Customization

### Adding New Images
1. Login as admin
2. Navigate to the admin panel
3. Use the upload interface to add new images
4. Images are automatically displayed in the examples gallery

### Modifying Styles
- Edit `css/styles.css` for global styles
- Edit `css/examples.css` for examples page styles
- Edit `css/admin.css` for admin panel styles

### Changing Admin Credentials
1. Edit `config/secrets.php`
2. Update the username and/or password
3. Ensure the file remains excluded from version control

## Security Considerations

- Change the default admin password before deployment
- Regularly update PHP and server software
- Monitor file uploads for suspicious activity
- Consider implementing additional security measures for production use
- Backup the `config/secrets.php` file securely

## Support

For technical support or customization requests, please contact the development team.