<?php
/**
 * Legacy Library Login Redirect
 * Redirects to the unified modern authentication portal
 */
header("Location: login.php");
exit();
?>



<!DOCTYPE html>
<html>
<head>
	<title>Home-SMS</title>


	<style type="text/css">

	                 /*Header section*/

		.header_left
		{
			height: 80px;
		    width: 1004px;
		    background-color: skyblue;
		
			margin-left: 170px;
		
	    }

                       /*Header Logo Section*/

	    .header_img
	    {
	    	margin-left: 50px;
	    }

	                        /*Right Section Logo*/

	    .header_right
	    {
          margin-left: 400px;
	    }
                       
                       /*Body Section*/

	    .body
	    {
	    	width: 1000px;
	    	height: 500px;
	    	margin-left: 170px;
	  		border: 2px solid black;
	    	background-color: white;

	    	border-bottom-left-radius: 10px;
	    	border-bottom-right-radius: 10px;
	    }
	    

	                     /*Marquee Section*/

	    .menu
	    {
	    	background-color: black;
	    	height: 40px;
	    	width: 980px;
	    	margin-left: 10px;
	    }

	    .header_text
	    {
	    	color: white;
	    	font-size: 25px;
	    	margin-left: 350px;
	    }

	    .form
	    {
	    	margin-left: 330px;
	    }

	    input[type=text]
	    {
	    	height: 30px;
	    	width: 300px;
	        color: black;
	        font-weight: bold;
	        margin-left:30px;
	        border-radius: 8px;
	    }

	     input[type=password]
	    {
	    	height: 30px;
	    	width: 300px;
	        color: black;
	        font-weight: bold;
	        margin-left:30px;
	        border-radius: 8px;

	    }


	   

	   

	    
       input[type=submit]
       {
       	 height: 35px;
       	 width: 120px;
       	 font-size: 15px;
       	 cursor: pointer;
       	 background-color: green;
       	 color: white;
       	 font-weight: bold;
       	 margin-left:120px;
       	 font-size: 16px;
       	 border-radius: 8px;
       	
       }

       /* input[type=reset]
       {
       	 height: 35px;
       	 width: 120px;
       	 font-size: 15px;
       	 cursor: pointer;
       	 background-color: green;
       	 color: white;
       	 font-weight: bold;
       }*/

       b
       {
       	 font-size: 20px;
       	 font-weight: bold;
       	 /*color: #8b0a50;*/
       	 margin-left:30px;
       }

       .under_form
       {
       	 border : 2px solid black;

       	 height: 250px;
       	 border-radius: 6px;

       	 width: 360px;
       	background-color: #4aff78;

       }
	    					

	</style>


</head>

<body bgcolor="lightyellow">

                                           <!-- Header Section -->

	<div class="header_left">

		<a href="index.php"><img src="headeta.png" class="header_img"></a> 

		     
	</div>

                                            <!-- Menu and Body Section -->


    <div class="body">
    	<div class="menu">

 
    		      <b class="header_text">  ***** Library Login*****</b>
    	  
    		
    	</div>  <br><br>

    	                                    <!-- Body work -->
            <div class="form">

                 <div class="under_form">

                    <div style="color: #b0ff62; font-weight: bold; font-size: 25px; text-align: center; border: inset; background-color: #008c23; padding-top: 10px; height:  40px;" >  Library Login</div>

                     <form name="frm" action="" method="post" style="padding-top: 20px;">

                      <!-- <b>E-mail :</b> <br> -->
                 	 <input type="text" name="email" value="" placeholder="Please Enter Username"></a>

                 	 <br><br>

                 	<!--  <b>Password :</b> <br> -->
                 	 <input type="password" name="password" value="" placeholder="Please Enter Password"></a>


                 	 <br><br>

                 	 <input type="submit" name="submit" value="Login"></a>

                 	 </form>


                 </div>
         </div>
    </div>
</body>
</html>