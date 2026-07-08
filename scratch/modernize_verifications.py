import os

targets = [
    {
        "file": "adminSide/staffCrud/updateStaffVerify.php",
        "subtitle": "Admin Credentials needed to Edit Staff",
        "button": "Edit Staff",
        "cancel": "../panel/staff-panel.php"
    },
    {
        "file": "adminSide/staffCrud/delete_staffVerify.php",
        "subtitle": "Admin Credentials needed to Delete Staff",
        "button": "Delete Staff",
        "cancel": "../panel/staff-panel.php"
    },
    {
        "file": "adminSide/menuCrud/createItemVerify.php",
        "subtitle": "Admin Credentials needed to Add Item",
        "button": "Add Item",
        "cancel": "../panel/menu-panel.php"
    },
    {
        "file": "adminSide/menuCrud/updateItemVerify.php",
        "subtitle": "Admin Credentials needed to Edit Item",
        "button": "Edit Item",
        "cancel": "../panel/menu-panel.php"
    },
    {
        "file": "adminSide/menuCrud/deleteMenuVerify.php",
        "subtitle": "Admin Credentials needed to Delete Item",
        "button": "Delete Item",
        "cancel": "../panel/menu-panel.php"
    },
    {
        "file": "adminSide/menuCrud/toggleStatusVerify.php",
        "subtitle": "Admin Credentials needed to Toggle Status",
        "button": "Toggle Status",
        "cancel": "../panel/menu-panel.php"
    },
    {
        "file": "adminSide/tableCrud/createTableVerify.php",
        "subtitle": "Admin Credentials needed to Add Table",
        "button": "Add Table",
        "cancel": "../panel/table-panel.php"
    },
    {
        "file": "adminSide/tableCrud/deleteTableVerify.php",
        "subtitle": "Admin Credentials needed to Delete Table",
        "button": "Delete Table",
        "cancel": "../panel/table-panel.php"
    },
    {
        "file": "adminSide/reservationsCrud/createReservationVerify.php",
        "subtitle": "Admin Credentials needed to Add Reservation",
        "button": "Add Reservation",
        "cancel": "../panel/reservation-panel.php"
    },
    {
        "file": "adminSide/reservationsCrud/deleteReservationVerify.php",
        "subtitle": "Admin Credentials needed to Delete Reservation",
        "button": "Delete Reservation",
        "cancel": "../panel/reservation-panel.php"
    }
]

template = """<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Verification - X Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            background-color: #080C14;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.08) 0px, transparent 45%),
                radial-gradient(at 100% 0%, rgba(79, 70, 229, 0.08) 0px, transparent 45%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            border-color: rgba(220, 20, 60, 0.3);
        }

        h1.brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 2.6rem;
            letter-spacing: -0.03em;
            color: #f8fafc;
            text-transform: uppercase;
        }

        h1.brand-title span {
            color: crimson;
        }

        h2.page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.6rem;
            color: #94a3b8;
            margin-bottom: 5px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        h5.panel-subtitle {
            font-size: 1.15rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group label {
            color: #94a3b8;
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Premium inputs */
        .form-control {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: #f8fafc !important;
            padding: 12px 18px !important;
            height: auto !important;
            transition: all 0.2s ease !important;
        }

        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.45) !important;
            border-color: crimson !important;
            box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.25) !important;
            color: white !important;
            outline: none !important;
        }

        .input-group-append button {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-left: none !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            color: #94a3b8 !important;
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
            padding-left: 15px;
            padding-right: 15px;
            transition: all 0.2s ease;
        }

        .input-group-append button:hover {
            color: white;
            background-color: rgba(220, 20, 60, 0.2) !important;
        }

        .btn-submit {
            background: crimson;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: bold;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
            width: 100%;
        }

        .btn-submit:hover {
            background: #b30e2f;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(220, 20, 60, 0.45);
            color: white;
        }

        .btn-cancel {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-cancel:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a class="nav-link text-center p-0" href="../../customerSide/home/home.php"> 
            <h1 class="brand-title mb-1">X <span>HOTEL</span></h1>
        </a>
        <h2 class="page-title">Admin Login</h2>
        <h5 class="panel-subtitle">__SUBTITLE__</h5>
        
        <form action="" method="post">
            <div class="form-group mb-4">
                <label>Admin Id</label>
                <input type="number" name="admin_id" class="form-control" placeholder="Enter Admin ID" required>
            </div>

            <div class="form-group mb-4">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <div class="input-group-append">
                        <button class="toggle-password-btn" type="button" data-target="password">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group mt-2">
                <button class="btn btn-submit mb-3" type="submit" name="submit" value="submit">__BUTTON__</button>
                <a class="btn btn-cancel d-block text-center" href="__CANCEL__" >Cancel</a>
            </div>
        </form>
    </div>

    <script>
    document.querySelectorAll('.toggle-password-btn').forEach(button => {
        button.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            if (input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            }
        });
    });
    </script>
</body>
</html>"""

for target in targets:
    file_path = target["file"]
    if not os.path.exists(file_path):
        print(f"Skipped {file_path}: File not found")
        continue
    
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    # Locate the split at <?php ... ?> section
    split_index = content.find("?>")
    if split_index == -1:
        print(f"Error {file_path}: '?>' not found in file")
        continue
    
    php_part = content[:split_index + 2]
    
    # Replace tokens
    html_part = template.replace("__SUBTITLE__", target["subtitle"])
    html_part = html_part.replace("__BUTTON__", target["button"])
    html_part = html_part.replace("__CANCEL__", target["cancel"])
    
    new_content = php_part + "\n\n" + html_part
    
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(new_content)
        
    print(f"Successfully modernized {file_path}")
