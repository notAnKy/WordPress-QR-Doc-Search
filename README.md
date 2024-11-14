# WordPress-QR-Doc-Search

A powerful WordPress plugin for QR code scanning and document search, allowing users to easily access PDF files by scanning QR codes or entering specific references.

## Features

### 1. QR Code Scanning with URL Input
- Users can enter a URL manually.
- Scanning a QR code adds extra information to the URL for seamless access.

### 2. QR Code Scanning to Open PDFs by Folder Name
- Users can access organized folder structures in the WordPress uploads directory.
- Each folder name serves as a reference, allowing a QR code scan to open the corresponding PDF file.

### 3. PDF Search by Reference
- A search bar allows users to input a reference.
- If the reference exists, the associated PDF file opens automatically.

## Key Considerations

- **Performance**: Optimized for efficient QR scanning and PDF retrieval.
- **Security**: Safely handles user inputs (URLs, file paths).
- **Usability**: Designed with a user-friendly, intuitive interface.
- **Compatibility**: Works across various browsers and WordPress versions.

## Plugin Setup

### Admin Settings

The plugin provides a settings page with options for:
1. **URL Scanning**
2. **Folder-Based QR Code Scanning**
3. **PDF Reference Search**

Changes can be saved using the **Save Changes** button.

#### Document Management
The **Data** page allows administrators to:
- Upload PDFs using the **Choose Files** button.
- View a table listing each PDF’s ID, name, and reference.
- Delete files when needed.

### User Interface

#### Usage Options

1. **Option 1**: Scanning a QR code appends the scanned data to an entered URL.
2. **Option 2**: Scanning a QR code with a folder reference opens the PDF from that folder.
3. **Option 3**: Users can enter a PDF reference in the search bar to open the corresponding file.

## Screenshots
_Example Screenshots for UI:_
- **Option 1**: QR Code with URL
- **Option 2**: Folder-Referenced QR Code
- **Option 3**: PDF Search by Reference

## Installation

1. Download and upload the plugin to your WordPress `plugins` directory.
2. Activate the plugin in the WordPress dashboard.
3. Configure settings in the **QR-Doc-Search** settings page.

## Contributing

Contributions are welcome! Please fork the repository and create a pull request for any feature additions or bug fixes.

## License

This project is licensed under the MIT License.
