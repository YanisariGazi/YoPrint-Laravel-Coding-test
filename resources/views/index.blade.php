<!-- resources/views/uploads/index.blade.php -->
<!doctype html>
<html>

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CSV Uploads</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px
        }

        .upload-box {
            border: 2px dashed #ccc;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .upload-left {
            flex: 1;
            padding-right: 20px
        }

        .upload-btn {
            padding: 10px 16px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left
        }

        .status-pending {
            background: #fff3cd
        }

        .status-processing {
            background: #d1ecf1
        }

        .status-completed {
            background: #d4edda
        }

        .status-failed {
            background: #f8d7da
        }
    </style>
</head>

<body>
    <h2>Upload CSV</h2>
    <div class="upload-box">
        <div class="upload-left">
            <form id="uploadForm" enctype="multipart/form-data">
                <div style="margin-bottom:8px">Select file / Drag and drop</div>
                <input type="file" name="file" id="file" accept=".csv,text/csv">
                <button type="submit" class="upload-btn">Upload File</button>
            </form>
            <div id="message" style="margin-top:8px;color:#666"></div>
        </div>
        <div style="width:160px;text-align:right">
            <img src="" alt="" style="opacity:0.2;width:120px">
        </div>
    </div>

    <h3>Recent Uploads</h3>
    <table id="uploadsTable">
        <thead>
            <tr>
                <th>Time</th>
                <th>File Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function fetchUploads() {
            const res = await fetch('/api/uploads/list');
            const { data } = await res.json();
            console.log('oiii',data)
            const tbody = document.querySelector('#uploadsTable tbody');
            tbody.innerHTML = '';
            data.forEach(u => {
                const tr = document.createElement('tr');
                const created = new Date(u.created_at);
                const now = new Date();
                const diffMs = now - created;
                const diffMin = Math.floor(diffMs / 60000);

                // ubah ke label waktu relatif
                let ago = '';
                if (diffMin < 1) ago = 'just now';
                else if (diffMin < 60) ago = `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
                else if (diffMin < 1440) {
                    const hrs = Math.floor(diffMin / 60);
                    ago = `${hrs} hour${hrs > 1 ? 's' : ''} ago`;
                } else {
                    const days = Math.floor(diffMin / 1440);
                    ago = `${days} day${days > 1 ? 's' : ''} ago`;
                }

                const time = created.toLocaleString();

                tr.innerHTML = `
                    <td>${time}<br><small>(${ago})</small></td>
                    <td>${u.filename}</td>
                    <td class="status-${u.status}">${u.status}${u.error ? ' - ' + u.error : ''}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // poll every 3 seconds
        fetchUploads();
        setInterval(fetchUploads, 3000);

        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('file');
            if (!fileInput.files.length) {
                alert('Please select a CSV file.');
                return;
            }
            const form = new FormData();
            form.append('file', fileInput.files[0]);

            document.getElementById('message').innerText = 'Uploading...';

            const res = await fetch('/api/uploads', {
                method: 'POST',
                // headers: {
                //     'X-CSRF-TOKEN': token
                // },
                body: form
            });
            const { message, code, data } = await res.json();
            console.log(data);
            if (code == 201) {
                document.getElementById('message').innerText = message || 'Uploaded';
                fetchUploads();
            } else {
                document.getElementById('message').innerText = (message || 'Upload failed');
            }
        });

        const dropArea = document.querySelector('.upload-box');
        dropArea.addEventListener('dragover', e => {
            e.preventDefault();
            dropArea.style.borderColor = '#666';
        });
        dropArea.addEventListener('dragleave', e => {
            dropArea.style.borderColor = '#ccc';
        });
        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            dropArea.style.borderColor = '#ccc';
            const files = e.dataTransfer.files;
            if (files.length) {
                document.getElementById('file').files = files;
            }
        });
    </script>
</body>

</html>
