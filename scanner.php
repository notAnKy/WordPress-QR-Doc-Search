<?php
/**
 * Plugin Name: scanner
 * Description: QR Code Scanner
 * Author: med ali jemmali
 * Version: 2.0.0
 * Text Domain: scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Scanner {

    private $upload_dir;

    public function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->upload_dir = trailingslashit($this->upload_dir['basedir']) . 'scanner_data';
        $this->create_scanner_folder();
        add_shortcode('scanner_shortcode', array($this, 'create_scanner'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_scanner_settings', array($this, 'save_scanner_settings'));
        add_action('wp_footer', array($this, 'load_custom_script'));
        add_action('admin_menu', array($this, 'add_data_page'));
        add_action('wp_ajax_delete_pdf_file', array($this, 'delete_pdf_file'));
        add_action('wp_enqueue_scripts', array($this, 'load_instascan_library'));
    }

    private function create_scanner_folder() {
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }

    public function create_scanner() {
        $selected_option = isset($_COOKIE['selected_option']) ? esc_attr($_COOKIE['selected_option']) : 'option1';
        
        echo '<div id="scanner-container">';
        
        if ($selected_option === 'option3') {
            echo '<input type="text" id="pdf_reference" name="pdf_reference" placeholder="Enter PDF File Reference">';
            echo '<button id="search-button" class="custom-search-button">Search</button>';
        } else {
            $suffix = ($selected_option === 'option1') ? '1' : '2';
            echo '<button id="scan-button" class="custom-scan-button"><img src="' . plugins_url('iconN.png', __FILE__) . '" width="30px" height="30px" style="margin-right: 5px;">Scanner' . $suffix . '</button>';
        }
    
        echo '</div>';
        
        echo '<div id="scanner-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeScannerModal()">&times;</span>
                    <div id="scanner-container">
                        <video id="scanner-video" width="100%" style="display: none;"></video>
                    </div>
                </div>
            </div>';
        
        echo '<script>
                function openScannerModal() {
                    document.getElementById("scanner-modal").style.display = "block";
                }
                function closeScannerModal() {
                    var videoElement = document.getElementById("scanner-video");
                    if (videoElement && videoElement.srcObject) {
                        var tracks = videoElement.srcObject.getTracks();
                        tracks.forEach(track => track.stop());
                    }
                    document.getElementById("scanner-modal").style.display = "none";
                }
                document.getElementById("scan-button").addEventListener("click", openScannerModal);
            </script>';
        
        if ($selected_option === 'option3') {
            echo '<script>
                    document.getElementById("search-button").addEventListener("click", function () {
                        var pdfReference = document.getElementById("pdf_reference").value;
                        if (pdfReference.trim() !== "") {
                            var xhr = new XMLHttpRequest();
                            xhr.open("GET", "' . admin_url('admin-ajax.php') . '?action=search_pdf_file&pdf_reference=" + pdfReference, true);
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.exists) {
                                        window.open(response.file_url, "_blank");
                                    } else {
                                        alert("File not found: " + pdfReference);
                                    }
                                }
                            };
                            xhr.send();
                        } else {
                            alert("Please enter a PDF File Reference.");
                        }
                    });
                </script>';
        }
    
        echo '<style>
                #scanner-modal{display:none;position:fixed;z-index:1;left:50%;top:50%;transform:translate(-50%,-50%);width:60%;max-width:400px;height:auto;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.4);} 
                .modal-content{background-color:#fefefe;padding:20px;border:1px solid #888;width:100%;} 
                .close{color:#aaa;float:right;font-size:28px;font-weight:bold;} 
                .close:hover, .close:focus{color:black;text-decoration:none;cursor:pointer;}
                .custom-scan-button {background-color: rgb(34, 255, 0);color: black;padding: 10px 10px;border: none; border-radius: 10px; cursor: pointer;}
                .custom-search-button {background-color: rgb(34, 255, 0);color: black;padding: 10px 10px;border: none; border-radius: 10px; cursor: pointer;}
                #pdf_reference {
                    width:40%; /* Adjust the width as needed */
                    padding: 8px;
                    margin-right: 10px;
                    box-sizing: border-box;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    font-size: 16px;
                }
        
                #search-button {
                    background-color: rgb(34, 255, 0);
                    color: black;
                    padding: 10px 15px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 16px;
                }
        
                #search-button:hover {
                    background-color: #28a745; /* Change color on hover as needed */
                }
            </style>';
    }       

    public function load_instascan_library() {
        wp_enqueue_script(
            'scanner_instascan',
            plugin_dir_url(__FILE__) . 'instascan.js',
            array('jquery'),
            '1.0.0', 
            true 
        );
    }

    public function load_custom_script() {
        $saved_url = esc_url(get_option('scanner_url'));
        $upload_dir_url = esc_url(trailingslashit($this->upload_dir));
        $selected_option = isset($_COOKIE['selected_option']) ? esc_attr($_COOKIE['selected_option']) : 'option1';
    
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const video = document.getElementById('scanner-video');
                const scanButton = document.getElementById('scan-button');
                const searchButton = document.getElementById('search-button');
    
                if (scanButton) {
                    scanButton.addEventListener('click', function () {
                        video.style.display = 'block';
    
                        const scanner = new Instascan.Scanner({ video: video });
    
                        const savedUrl = '<?php echo $saved_url; ?>';
                        const uploadDirUrl = '<?php echo $upload_dir_url; ?>';
    
                        scanner.addListener('scan', function (content) {
                            <?php if ($selected_option === 'option1'): ?>
                                window.location.href = savedUrl + uploadDirUrl + content;
                            <?php elseif ($selected_option === 'option2'): ?>
                                const siteUrl = '<?php echo esc_url(site_url()); ?>';
                                const pdfFilePath = siteUrl + '/wp-content/uploads/scanner_data/' + content + '.pdf';
    
                                fetch(pdfFilePath)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('PDF file not found');
                                        }
                                        return response.text();
                                    })
                                    .then(data => {
                                        window.open(pdfFilePath);
                                        closeScannerModal();
                                    })
                                    .catch(error => {
                                        alert('Error: ' + error.message);
                                        closeScannerModal();
                                    });
                            <?php endif; ?>
                        });
    
                        Instascan.Camera.getCameras().then(function (cameras) {
                            if (cameras.length > 0) {
                                scanner.start(cameras[0]);
                            } else {
                                alert('No cameras found.');
                            }
                        }).catch(function (e) {
                            console.error(e);
                        });
                    });
                }
    
                if (searchButton) {
                    searchButton.addEventListener('click', function () {
                        var pdfReference = document.getElementById("pdf_reference").value;
                        if (pdfReference.trim() !== "") {
                            const siteUrl = '<?php echo esc_url(site_url()); ?>';
                            const pdfFilePath = siteUrl + '/wp-content/uploads/scanner_data/' + pdfReference + '.pdf';
                                fetch(pdfFilePath)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('PDF file not found');
                                        }
                                        return response.text();
                                    })
                                    .then(data => {
                                        window.open(pdfFilePath);
                                    })
                                    .catch(error => {
                                        alert('Error: ' + error.message);
                                    });
                        } else {
                            alert("Please enter a PDF File Reference.");
                        }
                    });
                }
            });
        </script>
        <?php
    }    
    
    public function add_admin_menu() {
        add_menu_page(
            'Scanner Settings',
            'Scanner',
            'manage_options',
            'scanner-settings',
            array($this, 'admin_page'),
            plugin_dir_url(__FILE__) . 'i.png',
            20
        );
    }

    public function admin_page() {
        $saved_url = esc_url(get_option('scanner_url'));
        $selected_option = isset($_COOKIE['selected_option']) ? esc_attr($_COOKIE['selected_option']) : 'option1';
    
        echo '
        <style>
            fieldset {
                border: 1px solid #ccc;
                width: 50%;
                padding: 10px;
                margin-bottom: 10px;
            }
        </style>
        <form method="post" action="admin-post.php" enctype="multipart/form-data">
            <fieldset>
                <legend><input type="radio" name="option" value="option1" id="t1" ' . ($selected_option === 'option1' ? 'checked' : '') . '>Option 1</legend>
                <p>URL : <input type="text" name="scanner_url" value="' . esc_attr($saved_url) . '">
                <button type="submit">Save</button></p>
            </fieldset>
            <fieldset>
                <legend><input type="radio" name="option" value="option2" id="t2" ' . ($selected_option === 'option2' ? 'checked' : '') . '>Option 2</legend>
                <p>Search with Qr code</p>
            </fieldset>
            <fieldset>
                <legend><input type="radio" name="option" value="option3" id="t3" ' . ($selected_option === 'option3' ? 'checked' : '') . '>Option 3</legend>
                <p>Search with file reference</p>
            </fieldset>
            <input type="hidden" name="action" value="save_scanner_settings">
            <button type="submit">Save changes</button></p>
        </form>
    
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const radioButtons = document.getElementsByName("option");
    
                for (const radioButton of radioButtons) {
                    radioButton.addEventListener("change", function () {
                        document.cookie = "selected_option=" + this.value;
                    });
                }
            });
        </script>';
    }
    
    public function save_scanner_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Access Denied');
        }
    
        if (isset($_POST['submit_option2'])) {
            $pdf_file_name = sanitize_file_name($_FILES['pdf_file']['name']);
            $pdf_file_path = trailingslashit($this->upload_dir) . $pdf_file_name;
    
            if (file_exists($pdf_file_path)) {
                wp_die('Error: File with the same name already exists.');
            }
    
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdf_file_path)) {
                echo 'PDF File added successfully.';
            } else {
                wp_die('Error uploading PDF file.');
            }
        } else {
            $url = sanitize_text_field($_POST['scanner_url']);
            update_option('scanner_url', $url);
    
            setcookie('selected_option', $_POST['option'], time() + 36000, '/');
        }
    
        wp_redirect(admin_url('admin.php?page=scanner-settings'));
        exit();
    }
    

    public function add_data_page() {
        add_submenu_page(
            'scanner-settings',
            'Data',
            'Data',
            'manage_options',
            'scanner-data',
            array($this, 'data_page')
        );
    }

    private function display_upload_stats($upload_result) {
        echo '<div id="upload-stats-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUploadStatsModal()">&times;</span>
                    <h3>Upload Statistics</h3>
                    <p>Total Uploads: <span id="total-uploads">' . (isset($_FILES['pdf_files']['name']) ? count($_FILES['pdf_files']['name']) : 0) . '</span></p>
                    <p>Successful Uploads: <span id="successful-uploads">' . count($upload_result['success']) . '</span></p>
                    <p>Name Errors: <span id="name-errors">' . count($upload_result['name_errors']) . '</span></p>
                    <p>Duplicate Errors: <span id="duplicate-errors">' . count($upload_result['duplicate_errors']) . '</span></p>';
    
        if (!empty($upload_result['name_errors'])) {
            echo '<p>Files Not Uploaded:</p>';
            echo '<ul>';
            foreach ($upload_result['name_errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
        }
    
        echo '</div>
              </div>';
    
        echo '<style>
                #upload-stats-modal {display:none;position:fixed;z-index:1;left:50%;top:50%;transform:translate(-50%,-50%);width:60%;max-width:400px;height:auto;overflow:auto;background-color:rgb(255,255,255);box-shadow: 0 4px 8px rgba(0,0,0,0.2);border-radius: 10px;}
                .modal-content {padding:20px;} 
                .close {color:#aaa;float:right;font-size:28px;font-weight:bold;} 
                .close:hover, .close:focus{color:black;text-decoration:none;cursor:pointer;}
              </style>';
    
        echo '<script>
                function openUploadStatsModal() {
                    document.getElementById("upload-stats-modal").style.display = "block";
                }
                function closeUploadStatsModal() {
                    document.getElementById("upload-stats-modal").style.display = "none";
                }
                openUploadStatsModal();
              </script>';
    }
    
    public function data_page() {
        echo '<div class="wrap">
            <h1>Scanner Data</h1>';
    
        if (isset($_FILES['pdf_files']['name'])) {
            $upload_result = $this->handle_multiple_file_upload();
            $this->display_upload_stats($upload_result);
        }
    
        echo '<form method="post" enctype="multipart/form-data">
                <label for="pdf_files">Choose PDF files:</label>
                <input type="file" name="pdf_files[]" accept=".pdf" multiple>
                <button type="submit" name="submit_file">Upload</button>
              </form>';
    
        $pdf_files = $this->get_pdf_files();
    
        if (!empty($pdf_files)) {
            echo '<table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PDF Name</th>
                            <th>Page reference</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';
    
            foreach ($pdf_files as $pdf_file) {
                echo '<tr>
                        <td>' . $pdf_file['id'] . '</td>
                        <td><a href="' . esc_url(site_url("/wp-content/uploads/scanner_data/{$pdf_file['name']}")) . '" target="_blank">' . $pdf_file['name'] . '</a></td>
                        <td>' . pathinfo($pdf_file['name'], PATHINFO_FILENAME) . '</td>
                        <td><button onclick="deletePdfFile(' . $pdf_file['id'] . ', \'' . $pdf_file['name'] . '\')">Delete</button></td>
                    </tr>';
            }
    
            echo '</tbody></table>';
        } else {
            echo '<p>No PDF files found in the scanner_data folder.</p>';
        }
    
        echo '</div>';
        ?>
        <script>
            function deletePdfFile(id, filename) {
                if (confirm('Are you sure you want to delete ' + filename + '?')) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            location.reload();
                        } else {
                            alert('Failed to delete ' + filename);
                        }
                    };
                    xhr.send('action=delete_pdf_file&id=' + id + '&filename=' + filename);
                }
            }
        </script>
        <?php
    }     

    private function handle_multiple_file_upload() {
        $upload_result = array('success' => array(), 'name_errors' => array(), 'duplicate_errors' => array());
    
        if (!isset($_FILES['pdf_files'])) {
            return $upload_result;
        }
    
        foreach ($_FILES['pdf_files']['name'] as $key => $value) {
            $pdf_file_name = sanitize_file_name($_FILES['pdf_files']['name'][$key]);
    
            if (!empty($pdf_file_name) && preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $pdf_file_name)) {
                $pdf_file_path = trailingslashit($this->upload_dir) . $pdf_file_name;
    
                if (file_exists($pdf_file_path)) {
                    $upload_result['duplicate_errors'][] = $pdf_file_name;
                } else {
                    if (move_uploaded_file($_FILES['pdf_files']['tmp_name'][$key], $pdf_file_path)) {
                        $upload_result['success'][] = $pdf_file_name;
                    } else {
                        $upload_result['name_errors'][] = $pdf_file_name;
                    }
                }
            } else {
                $upload_result['name_errors'][] = $pdf_file_name;
            }
        }
    
        return $upload_result;
    }        

    public function delete_pdf_file() {
        if (!current_user_can('manage_options') || !isset($_POST['id']) || !isset($_POST['filename'])) {
            wp_die('Access Denied');
        }
    
        $id = intval($_POST['id']);
        $filename = sanitize_file_name($_POST['filename']);
    
        $file_path = trailingslashit($this->upload_dir) . $filename;
    
        if (file_exists($file_path) && is_writable($file_path)) {
            unlink($file_path);
            echo 'File deleted successfully';
        } else {
            echo 'Failed to delete file';
        }
    
        wp_die();
    }

    private function get_pdf_files() {
        $pdf_files = array();

        if (is_dir($this->upload_dir)) {
            $files = scandir($this->upload_dir);
            $id = 1;

            foreach ($files as $file) {
                if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                    $pdf_files[] = array(
                        'id'   => $id++,
                        'name' => $file,
                    );
                }
            }
        }

        return $pdf_files;
    }
}

new Scanner;
?>