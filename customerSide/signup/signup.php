<?php
require_once '../config.php';

	
$showAlert = false;
$showError = false;
$exists=false;
	
if($_SERVER["REQUEST_METHOD"] == "POST") {
	
	// Include file which makes the
	// Database Connection.
	
	$username = $_POST["username"];
	$password = $_POST["password"];
	$cpassword = $_POST["cpassword"];
			
	
	$sql = "Select * from users where username='$username'";
	
	$result = mysqli_query($conn, $sql);
	
	$num = mysqli_num_rows($result);
	
	// This sql query is use to check if
	// the username is already present
	// or not in our Database
	if($num == 0) {
		if(($password == $cpassword) && $exists==false) {
	
			$hash = password_hash($password,
								PASSWORD_DEFAULT);
				
			// Password Hashing is used here.
			$sql = "INSERT INTO `users` ( `username`,
				`password`, `date`) VALUES ('$username',
				'$hash', current_timestamp())";
	
			$result = mysqli_query($conn, $sql);
	
			if ($result) {
				$showAlert = true;
			}
		}
		else {
			$showError = "Passwords do not match";
		}	
	}// end if
	
if($num>0)
{
	$exists="Username not available";
}
	
}//end if
	
?>
	
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Sign Up - X Hotel</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
	<style>
		body {
			font-family: 'Inter', sans-serif;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			margin: 0;
			background-color: #06080c;
			background-image: linear-gradient(rgba(6, 8, 12, 0.8), rgba(6, 8, 12, 0.85)), url('../image/loginBackground.jpg');
			background-size: cover;
			background-position: center;
			background-repeat: no-repeat;
			background-attachment: fixed;
			color: #f8fafc;
			padding: 20px;
		}

		.register-container {
			width: 100%;
			max-width: 480px;
			padding: 40px;
			background: rgba(15, 23, 42, 0.7);
			backdrop-filter: blur(20px) saturate(180%);
			border: 1px solid rgba(255, 255, 255, 0.08);
			border-radius: 24px;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
			transition: border-color 0.3s ease;
		}

		.register-container:hover {
			border-color: rgba(220, 20, 60, 0.3);
		}

		h1.brand-title {
			font-family: 'Outfit', sans-serif;
			font-weight: 800;
			font-size: 2.8rem;
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
			font-size: 1.8rem;
			color: #94a3b8;
			margin-bottom: 30px;
			text-align: center;
		}

		.form-group label {
			color: #94a3b8;
			font-size: 1.1rem;
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
			font-size: 1.3rem;
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

		.link-text {
			color: #94a3b8;
			font-size: 1.2rem;
			margin-top: 25px;
			text-align: center;
		}

		.link-text a {
			color: crimson;
			font-weight: 600;
			transition: all 0.2s ease;
		}

		.link-text a:hover {
			color: #ff3355;
			text-decoration: none;
		}

		.alert-container {
			width: 100%;
			max-width: 480px;
			margin-bottom: 20px;
		}
	</style>
</head>
<body>
	<div class="alert-container">
		<?php
			if($showAlert) {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #a7f3d0; border-radius: 12px; font-size: 1.2rem;">
					<strong>Success!</strong> Your account is now created and you can login.
					<button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #a7f3d0;">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>';
			}
			if($showError) {
				echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 12px; font-size: 1.2rem;">
					<strong>Error!</strong> '. $showError.'
					<button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #fca5a5;">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>';
			}
			if($exists) {
				echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 12px; font-size: 1.2rem;">
					<strong>Error!</strong> '. $exists.'
					<button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #fca5a5;">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>';
			}
		?>
	</div>

	<div class="register-container">
		<a class="nav-link text-center p-0" href="../home/home.php#hero">
			<h1 class="brand-title text-center mb-1">X <span>HOTEL</span></h1>
		</a>
		<h2 class="page-title">Sign Up</h2>
		
		<form action="signup.php" method="post">
			<div class="form-group mb-3">
				<label for="username">Username</label>
				<input type="text" class="form-control" id="username" name="username" placeholder="Enter Username" required>
			</div>
		
			<div class="form-group mb-3">
				<label for="password">Password</label>
				<div class="input-group">
					<input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
					<div class="input-group-append">
						<button class="toggle-password-btn" type="button" data-target="password">
							<i class="fa fa-eye-slash" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</div>
		
			<div class="form-group mb-4">
				<label for="cpassword">Confirm Password</label>
				<div class="input-group">
					<input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="••••••••" required>
					<div class="input-group-append">
						<button class="toggle-password-btn" type="button" data-target="cpassword">
							<i class="fa fa-eye-slash" aria-hidden="true"></i>
						</button>
					</div>
				</div>
				<small class="form-text text-muted mt-2" style="font-size: 1rem; color: #64748b !important;">
					Make sure to type the exact same password
				</small>
			</div>	
		
			<button type="submit" class="btn btn-submit mt-2">SignUp</button>
		</form>

		<p class="link-text mb-0">Already have an account? <a href="../customerLogin/login.php">Proceed to Login</a></p>
	</div>

	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
	
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
</html>
