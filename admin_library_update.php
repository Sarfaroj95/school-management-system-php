
<!DOCTYPE html>
<html>
<head>
	<title>Admission Form</title>
	<link rel="stylesheet" type="text/css" href="">
	<link rel="stylesheet" type="text/css" href="button2.css">
	<style type="text/css">
		.header
		{
			height: 70px;
			width: 700px;
			background-color: green;
			margin-left: 330px;
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
			margin-top: -50px;
			margin-right: 500px;
			font-weight: bold;
			
		}
		.admin
		{
			height: 30px;
			width: 700px;
			background: black;
			margin-left: 330px;
			color: white;
			font-size: 20px;
			text-align: center;

		}
		.back
		{
			/*background-image: url("solid.jpg");*/
			background-color: #5e5eff;
		}
		.form
		{
			height: 400px;
			width: 700px;
			background-color: rgba(0,0,0,0.4);
			margin-left: 330px;


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
	    	/*border-color: red;*/
	    	text-align: center;
	    	background-color: green;
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
			border-color: green;
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
		}
		.fm
		{
			margin-top: -380px;
			margin-left: 460px;
		}
		input[type=submit]
		{ height: 30px; 
			width: 120px;
			 font-size: 12px;
			  font-weight: bold;
			   cursor: pointer;
			   background-color: #91da47;
			   margin-left: 100px; 
		}
	input[type=submit]:hover{ background-color: green; color: yellow; }
	input[type=reset]
		{ height: 30px; 
			width: 90px;
			 font-size: 12px;
			  font-weight: bold;
			   cursor: pointer;
			   background-color: red; 
			     margin-left: 10px; 
		}
	.bt:hover{ background-color: yellow; color: red; }

	input[type=button]
		{ height: 30px; 
			width: 90px;
			 font-size: 12px;
			  font-weight: bold;
			   cursor: pointer;
			   background-color: green;
			   margin-left: 10px; 
		}
	.b:hover{ background-color: yellow; color: red; }
	a
	{
		text-decoration: none;
	}
	.food
		{
			height: 30px;
			width: 700px;
			background: black;
			margin-left: px;
			margin-top: 70px;
		}

		
		 b
       {
       	 font-size: 20px;
       	 font-weight: bold;
       	 color: #ffffc6;
       }
       textarea
	    {
	    	width: 300px;
	    	
	    	font-weight: bold;
	    }
  input[type=radio]
	    {
	    	height: 15px;
	    	width: 15px;
	   color: #ffffc6;
	    }


	    input[type=text]
	    {
	    	height: 25px;
	    	width: 300px;
	           font-weight: bold;
	    }

	


	
	</style>
</head>
<body class="back">


	<div class="header">

	<div class="header_logo"><a href="admin_profile.php"> <img src="slicon.png" style="width: 50px; height: 50px; margin-top: 10px;"></a></div>


	<div class="header_beack"> <a href="logout_add.php"><img src="logouts.png" style="margin-left: 550px; margin-top: 25px;"></a></div>
	</div>

	<div class="header_name">School Management System</div>

	<div class="admin"> ---Admin Section---</div>
	 
	
	<div class="form">

					    <!-- Form Section -->
					    
              <?php
$a=$_GET['id'];
include "connection.php";
$qer=mysqli_query($conn,"select * from library_details where id='$a'");
$res=mysqli_fetch_assoc($qer);
?>
	

	<div style=" width: 450px; height: 300px; margin-left: 110px;">

<form method="post" style="margin-left: 50px; margin-top: -10px;">
	<table>
<tr>
	<td colspan="2" style="background: green; text-align: center; font-size:20px; padding-top: 5px; color: #f3fd84;width: 450px; height: 30px; margin-left: 130px; margin-top: 20px;">Update Now</td></tr>
	<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Name:</td><td><input type="text" value="<?php echo $res['sname'];  ?>" name="nm"><br></td></tr>
<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Class:</td><td><input type="text" value="<?php echo $res['class'];  ?>" name="cl"><br></td></tr>
<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Roll:</td><td><input type="text" value="<?php echo $res['roll'];  ?>" name="rl"><br></td></tr>
<tr> 
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Book:</td><td><input type="text" value="<?php echo $res['bookname'];  ?>" name="bk"><br></td></tr>
<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Author:</td><td><input type="text" value="<?php echo $res['authorname'];  ?>" name="auth"  ><br></td></tr>
<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Issue:</td><td><input type="date" value="<?php echo $res['withdroldate'];  ?>" name="wd" style="height: 30px;
        width: 300px;
        
        font-weight: bold;
        "><br></td></tr>
<tr>
<td style="font-size: 20px;
         font-weight: bold;
         color: #96ff2d;">Return:</td><td><input type="date" value="<?php echo $res['submisiondate'];  ?>" name="sb" style="height: 30px;
        width: 300px;
        
        font-weight: bold;
        "><br></td></tr><br>
<tr>
	<td></td>
<td><input type="submit" name="sub" value="Update"></td>
</tr>
</table>

</form>
</div>


<?php
if(isset($_POST['sub']))
{
$nm=$_POST['nm'];
$pa=$_POST['cl'];
$rol=$_POST['rl'];
$bok=$_POST['bk'];
$au=$_POST['auth'];
$w=$_POST['wd'];
$s=$_POST['sb'];
$qer=mysqli_query($conn,"update library_details set sname='$nm', class='$pa',roll='$rol',bookname='$bok',authorname='$au',withdroldate='$w',submisiondate='$s' where id='$a'");
if($qer)
{
echo "<script>window.location.href='admin_library_show.php';</script>";
}

}
?>

</body>
<div class="food"></div>
</div>
</body>
		
</div>
</html>

