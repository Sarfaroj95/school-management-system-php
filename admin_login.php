<?php
/**
 * Legacy Admin Login Redirect
 * Redirects to the unified modern authentication portal
 */
header("Location: login.php");
exit();
?>
<!DOCTYPE html>
<html>
<head>
	<title>Admission Form</title>
	<style type="text/css">
		.header
		{
			height: 70px;
			width: 600px;
			background-color: green;
			margin-left: 370px;
			margin-top: 50px;
			
			
		}
		.header_logo
		{
				float: left;
				margin-left: 50px;
		}
		.header_name
		{
			float: right;
			font-size: 30px;
			margin-top: 15px;
			margin-right: 100px;
			font-weight: bold;
		}
		.back
		{
			background-image: url("solid.jpg");
		}
		.form
		{
			height: 400px;
			width: 600px;
			background-color: rgba(0,0,0,0.4);
			margin-left: 370px;

		}
		.tab
		{
			margin-left: 150px;
			padding-top: 20px;
		}
		.in
		{
			font-size: 20px;
			font-weight: bold;
			margin-left: 

		}

		.name
		{
			font-size: 15px;
			margin-left: 10px;
		}
		.sk
		{
			font-size: 15px;
		}
		.sub
		{
			padding-top: 100px;
		}


		/*form section*/
		#head
	    {
	    	font-size: 25px;
	    	font-weight: bold;
	    	height: 40px;
	    	width: 300px;
	    	border-style: inset;
	    	border-color: #008040;
	    	text-align: center;
	    	background-color: #004a00;
	    	color: white;
	    	padding-top: 10px;
	    	
	    	
	    	/*margin-left: 550px;
            margin-top: 100px;*/
	    }
		#st
		{
			font-weight: bold;
			color: black;
			height: 150px;
			width: 300px;
			border-style: inset;
			border-color: #008040;
			border-bottom-right-radius: 5px;
			background-color: #408080;
			/*padding-top: 10px;
			margin-left: 550px;*/
			

		}
		.in
		{
			margin-left: 60px;
			margin-top: 6px;
			text-decoration: 5px;
			font-size: 14px;
			border-radius: 6px;
			text-align: center;
		}
		.frm
		{
			margin-left: 530px;
			margin-top: -320px;

		}
		input[type=submit]
		{ height: 30px; 
			width: 60px;
			 font-size: 12px;
			  font-weight: bold;
			   cursor: pointer;
			   background-color: green;
			   margin-left: 90px; 
			   border-radius: 6px;
		}
	.b:hover{ background-color: yellow; color: red; }
	input[type=reset]
		{ height: 30px; 
			width: 60px;
			 font-size: 12px;
			  font-weight: bold;
			   cursor: pointer;
			   background-color: red;
			   border-radius: 6px; 
		}
	.bt:hover{ background-color: yellow; color: red; }

	.admin
		{
			height: 30px;
			width: 600px;
			background: green;
			margin-left: 370px;
			margin-top: 80px;
			color: white;
			

		}
	</style>
</head>
<body class="back">


	<div class="header">

	<div class="header_logo"><a href="index.php"> <img src="slicon.png" style="width: 50px; height: 50px; margin-top: 10px;"></a></div>
	 <div class="header_name">School Management System</div>
	</div>
	
	<div class="form"></div>

					    <!-- Form Section -->

	<div class="frm">

	<div id="head">Admin Login</div>
	<form method="post">
	<div id="st">

	<input class="in" type="text" placeholder="Enter Your Username" name="user"><br><br>
	<input  class="in" type="Password" placeholder="Enter The Password" name="pass"><br><br>  
	<input class="b" type="submit" value="Login" name="submit">

	<input class="bt" type="reset" value="Reset" name="">
	</div>
</form>



				
</div>


		
</body>
</div><div class="admin"></div>
</html>

