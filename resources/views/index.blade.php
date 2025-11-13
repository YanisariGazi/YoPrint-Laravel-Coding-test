<!doctype html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CSV Uploads</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .drop-zone {
            border: 2px dashed #ced4da;
            border-radius: 6px;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .drop-zone.dragover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .status-pending {
            background: #fff3cd !important;
        }

        .status-processing {
            background: #cff4fc !important;
        }

        .status-completed {
            background: #d1e7dd !important;
        }

        .status-failed {
            background: #f8d7da !important;
        }
    </style>
</head>

<body class="bg-light py-4">
    <div class="container">
        <h2 class="mb-4 text-center">Upload CSV</h2>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="drop-zone mb-3" id="dropZone">
                        <div>Select file / Drag and drop</div>
                        <input type="file" name="file" id="file" accept=".csv,text/csv"
                            class="form-control mt-3 w-auto mx-auto" style="max-width:300px">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Upload File</button>
                    </div>
                </form>
                <div id="message" class="text-center mt-3 text-muted"></div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Uploads</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="uploadsTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="w-25">Time</th>
                            <th scope="col" class="w-25">File Name</th>
                            <th scope="col" class="w-50">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- dynamic rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function fetchUploads() {
            const res = await fetch('/api/uploads/list');
            const { data } = await res.json();
            const tbody = document.querySelector('#uploadsTable tbody');
            tbody.innerHTML = '';

            data.forEach(u => {
                const tr = document.createElement('tr');
                const created = new Date(u.created_at);
                const now = new Date();
                const diffMs = now - created;
                const diffMin = Math.floor(diffMs / 60000);

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
                    <td class="align-top">${time}<br><small class="text-muted">(${ago})</small></td>
                    <td class="align-top">${u.filename}</td>
                    <td class="status-${u.status} text-capitalize align-top">${u.status}${u.error ? ' - ' + u.error : ''}</td>
                `;
                tbody.appendChild(tr);
            });
        }

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
                body: form
            });
            const { message, code } = await res.json();

            document.getElementById('message').innerText =
                code == 201 ? (message || 'Uploaded successfully') : (message || 'Upload failed');
            fetchUploads();
        });

        const dropZone = document.getElementById('dropZone');
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) document.getElementById('file').files = files;
        });
    </script>
</body>

</html>
