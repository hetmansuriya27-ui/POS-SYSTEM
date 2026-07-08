import os

targets = [
    {
        "file": "adminSide/accountCrud/success_create_member_account.php",
        "redirect": "createMemberAccount.php"
    },
    {
        "file": "adminSide/accountCrud/success_create_staff_account.php",
        "redirect": "createStaffAccount.php"
    },
    {
        "file": "adminSide/customerCrud/success_createMembership.php",
        "redirect": "../panel/customer-panel.php"
    },
    {
        "file": "adminSide/customerCrud/success_createMembership.php.php",
        "redirect": "../panel/customer-panel.php"
    },
    {
        "file": "adminSide/staffCrud/succ_create_staff.php",
        "redirect": "../panel/staff-panel.php"
    },
    {
        "file": "adminSide/staffCrud/update_staff.php",
        "redirect": "../panel/staff-panel.php"
    }
]

template = """<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Status - X Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            text-align: center;
            padding: 40px 0;
            background-color: #080C14;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.08) 0px, transparent 45%),
                radial-gradient(at 100% 0%, rgba(79, 70, 229, 0.08) 0px, transparent 45%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #f8fafc;
            margin: 0;
        }

        .receipt-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            display: inline-block;
            max-width: 480px;
            width: 90%;
            margin: 0 auto;
            transition: border-color 0.3s ease;
        }

        .receipt-card:hover {
            border-color: rgba(16, 185, 129, 0.25);
        }

        .success-icon-circle {
            border-radius: 200px;
            height: 130px;
            width: 130px;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
            margin: 0 auto 30px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .success-icon-circle i.checkmark {
            color: #10b981;
            font-size: 70px;
            font-style: normal;
            font-weight: bold;
            line-height: 1;
        }

        .error-icon-circle {
            border-radius: 200px;
            height: 130px;
            width: 130px;
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
            margin: 0 auto 30px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .error-icon-circle i.custom-x {
            color: #ef4444;
            font-size: 60px;
            font-style: normal;
            font-weight: bold;
            line-height: 1;
        }

        h1 {
            color: #f8fafc;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 32px;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        h1.success-title {
            color: #10b981;
        }

        h1.error-title {
            color: #ef4444;
        }

        p {
            color: #94a3b8;
            font-size: 1.3rem;
            line-height: 1.8;
            margin: 0 0 20px 0;
            font-weight: 400;
        }

        .countdown-container {
            font-size: 1.1rem;
            color: #94a3b8;
            margin-top: 30px;
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        #countdown {
            color: crimson;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <?php if ($cardClass === 'alert-success'): ?>
            <div class="success-icon-circle">
                <i class="checkmark">✓</i>
            </div>
            <h1 class="success-title">Success</h1>
        <?php else: ?>
            <div class="error-icon-circle">
                <i class="custom-x">✘</i>
            </div>
            <h1 class="error-title">Error</h1>
        <?php endif; ?>
        
        <p><?php echo $message; ?></p>
        
        <div class="countdown-container">
            Redirecting in <span id="countdown">3</span> seconds...
        </div>
    </div>

    <script>
        var direction = "__REDIRECT__";
        
        function showPopup() {
            var i = 3;
            var countdownElement = document.getElementById("countdown");
            var countdownInterval = setInterval(function() {
                i--;
                countdownElement.textContent = i;
                if (i <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = direction;
                }
            }, 1000);
        }

        window.onload = showPopup;
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
        
    split_index = content.find("?>")
    if split_index == -1:
        print(f"Error {file_path}: '?>' not found in file")
        continue
        
    php_part = content[:split_index + 2]
    
    # We must ensure that $cardClass and $message are defined in PHP logic.
    # For target update_staff.php, let's make sure it has $cardClass defined
    if "update_staff.php" in file_path:
        # In update_staff.php, $cardClass is already defined!
        pass
        
    html_part = template.replace("__REDIRECT__", target["redirect"])
    
    new_content = php_part + "\n\n" + html_part
    
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(new_content)
        
    print(f"Successfully modernized {file_path}")
