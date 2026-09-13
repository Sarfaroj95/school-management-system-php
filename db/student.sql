-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2018 at 01:01 PM
-- Server version: 10.1.16-MariaDB
-- PHP Version: 5.5.38
-- PHP Version: 5.5.39

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_login`
--

CREATE TABLE `admin_login` (
  `id` int(11) NOT NULL,
  `user` varchar(8) NOT NULL,
  `pass` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_login`
--

INSERT INTO `admin_login` (`id`, `user`, `pass`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `atten`
--

CREATE TABLE `atten` (
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `atten`
--

INSERT INTO `atten` (`name`) VALUES
('Savana Parvin'),
('Savana Parvin');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `roll` varchar(10) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `class` varchar(10) DEFAULT NULL,
  `dat` varchar(50) DEFAULT NULL,
  `attent` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `roll`, `name`, `class`, `dat`, `attent`) VALUES
(1, '', 'SAR', 'X', '10/04/2018', ''),
(2, '', 'sagata', '', '', ''),
(3, '', 'sagata', '', '', ''),
(4, '', 'sagata', 'x', '', ''),
(5, '', 'sagata', 'x', 'jg', ''),
(6, '', 'sagata', 'x', '2018-05-02', ''),
(7, '', 'Savana Parvin', 'V', '', '1'),
(8, '3 ', 'Savana Parvin', 'V', '', '1'),
(9, '3 ', 'Savana Parvin', 'V', '', '1'),
(10, '3 ', 'Savana Parvin', 'V', '', '0'),
(11, '3 ', 'Savana Parvin', 'V', '', '1'),
(12, '3 ', 'Savana Parvin', 'V', '', ''),
(13, '3 ', 'Savana Parvin', 'V', '', '1'),
(14, '3 ', 'Savana Parvin', 'V', '', '1'),
(15, '3 ', 'Savana Parvin', 'V', '', ''),
(16, '3 ', 'Savana Parvin', 'V', '', ''),
(17, '3 ', 'Savana Parvin', 'V', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `attent_five`
--

CREATE TABLE `attent_five` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_eight`
--

CREATE TABLE `class_eight` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_eight`
--

INSERT INTO `class_eight` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(3, 'Sreedipto', 'male', 'kkkkkk', 'kkkkkk', 'kkkkkkkkk', 0, 'kkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 'kkkkkkkkkk', '2019-02-04', '25', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '50000', 60, 50, 50, 65, 60, 70, 355, '2018-07-23 05:54:11');

-- --------------------------------------------------------

--
-- Table structure for table `class_five`
--

CREATE TABLE `class_five` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_five`
--

INSERT INTO `class_five` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(32, 'Sakil Khan', 'male', 'Abu Khan ', 'Abu Khan ', 'Srinagar', 721127, 'sakil@gmail.com', '8545125236', '2018-05-17', '11', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '150000', 60, 50, 52, 60, 68, 70, 310, ''),
(33, 'Suman Das', 'male', 'Syemol Das', 'Keya Das', 'Bankura', 722122, 'suman23@gmail.com', '8001362514', '2008-06-17', '12', 'Jaganath School', 'Bengali', 'Muslim', '50000', 60, 55, 70, 65, 80, 70, 400, ''),
(34, 'Balaje', 'male', 'Dhiraj', 'Dhiraj', '721125', 0, 'mb', 'bjm', '2002-06-03', '14', 'jn', 'Bengali', 'Muslim', '5000', 40, 45, 55, 50, 60, 70, 320, '2018-05-07 08:18:42'),
(35, 'Asis Mitra', 'male', 'Mohima', 'Mohima', 'ss', 0, 'ss', 'ss', '2006-02-07', '12', 'gh', 'Bengali', 'Muslim', 'qw', 80, 70, 80, 75, 85, 85, 470, '2018-05-07 20:59:37'),
(37, 'sk sabir', 'male', 'sk abir', 'nasima begum', 'keshpur', 721150, 'sksabir12@gmail.com', '7764584458', '2008-03-01', '10', 'majurhati prathamik school', 'Bengali', 'Muslim', '50000', 60, 65, 50, 70, 62, 47, 354, '2018-05-09 10:09:50'),
(38, 'Rana mondal', 'male', 'Badal mondal', 'babli mondal', 'kalagram', 458750, 'ranamondal1@gmail.com', '779458745', '2007-05-02', '11', 'kalagram prathamik school', 'Bengali', 'Muslim', '48000', 60, 45, 78, 45, 56, 48, 332, '2018-05-09 10:15:33'),
(39, 'Julekha khatun', 'male', 'Sk sanabul', 'Morjina begum', 'chutergeriye', 721150, 'julekha1@gmail.com', '9647345684', '2007-02-05', '11', 'Chutergeriya prathamik school', 'Bengali', 'Muslim', '45000', 70, 78, 78, 73, 80, 90, 469, '2018-05-09 10:21:18'),
(40, 'Mamoni khatun', 'male', 'Kalo Gayen ', 'Jannatun begum', 'guyedachoti', 704251, 'mamoni1@gmail.com', '7845875458', '2008-05-03', '10', 'guyedachoti prathamik vidaloy', 'Bengali', 'Muslim', '60000', 30, 56, 45, 78, 45, 56, 310, '2018-05-09 10:30:38'),
(41, 'khusbu khatun', 'male', 'Kaji jalal uddin', 'Morjina begum', 'Majurhati', 721150, 'khusbu1@gmail.com', '9647348612', '2007-05-12', '11', 'Majuhati prathamik school', 'Bengali', 'Muslim', '50000', 45, 85, 74, 56, 65, 58, 383, '2018-05-09 10:35:21'),
(42, 'putul khatun', 'male', 'Kalo Gayen ', 'korina begum', 'mohisda', 721150, 'putui1@gmail.com', '9645871545', '2008-03-10', '10', 'Mohida prathamik school', 'Bengali', 'Muslim', '45000', 75, 48, 49, 46, 45, 74, 337, '2018-05-09 10:41:56'),
(43, 'sk Rohit', 'male', 'Sl ajijul', 'Moni begum', 'uchaher', 721150, 'skrohit1@gmail.com', '7797254845', '2007-02-10', '11', 'ucher prathamik viddaloy', 'Bengali', 'Muslim', '60000', 74, 47, 48, 84, 45, 64, 362, '2018-05-09 10:49:04'),
(44, 'Keya de', 'female', 'Abhik de', 'Monika de', 'Majurhati ', 721150, 'keya1@gmail.com', '6945125150', '2008-05-10', '10', 'Majuhati prathamik school', 'Bengali', 'Muslim', '800000', 74, 85, 89, 84, 86, 98, 516, '2018-05-09 10:56:23'),
(45, 'Robi das', 'male', 'Sam das', 'Sama das', 'Majurhati', 721150, 'robi1gmail.com', '9647348625', '2008-03-20', '10', 'Majuhati prathamik school', 'Bengali', 'Muslim', '60000', 85, 65, 85, 47, 49, 84, 415, '2018-05-09 11:01:07'),
(46, 'Ramij kaji', 'male', 'Japan kaji', 'Japan kaji', 'Majurhati', 721150, 'ramij2@gmail.com', '9647864584', '2007-05-15', '11', 'Majuhati prathamik school', 'Bengali', 'Muslim', '65000', 74, 48, 45, 46, 48, 49, 310, '2018-05-09 11:06:44'),
(48, 'Sara Khan', 'female', 'Sahid Khan', 'Saina Khan', 'Kolla', 784512, 'sara@gmail.com', '9765253685', '2018-07-16', '12', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '50000', 60, 78, 50, 85, 96, 60, 429, '2018-07-23 05:35:24'),
(49, 'kousik', 'male', 'Jisan', 'Tuhina Gayen ', 'KRISHNAPUR, CHANDRAKONA', 721127, 'sarfaroj95@gmail.com', '9732386292', '2011-06-14', '45', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '50000', 60, 58, 74, 85, 85, 85, 447, '2018-09-26 16:23:53');

-- --------------------------------------------------------

--
-- Table structure for table `class_nine`
--

CREATE TABLE `class_nine` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_nine`
--

INSERT INTO `class_nine` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(1, 'abhishek kumar', 'male', 'Voimik Mondal', 'Voimik Mondal', 'Jamsedpur', 700019, 'abhi@gmail.com', '8145253678', '1994-06-06', '24', '', '', '', '', 0, 0, 0, 0, 0, 0, 0, ''),
(3, 'Priyanka Das', 'female', 'Diapak das', 'Sila Das', 'Bankura,Ghoradhora', 722144, 'priyanka23@gmail.com', '9865251478', '2003-06-08', '15', 'Gopinath School', 'Bengali', 'Muslim', '60000', 76, 72, 68, 65, 75, 78, 434, '2018-05-07 21:14:46'),
(4, 'kkkkk', 'male', 'kkkkkk', 'kkkkkkkk', 'kkkkkkkk', 0, 'kkkkkkk', 'kkkkkkkkkk', '2019-02-03', '25', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '5000', 52, 63, 85, 98, 78, 58, 434, '2018-07-23 05:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `class_seven`
--

CREATE TABLE `class_seven` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_seven`
--

INSERT INTO `class_seven` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(25, 'Sarfaroj Gayen ', 'male', 'Samsul gayen ', 'Tuhina Gayen ', 'Krishnapur', 721127, 'sarfaroj@gmail.com', '8045680602', '1995-04-10', '23', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '15000', 60, 45, 50, 60, 70, 50, 420, ''),
(26, 'Riya Ponda', 'female', 'Niuton Ponda', 'Sriya Ponda', 'Jhargram', 722141, 'riya31@gmail.com', '7485964152', '2005-01-01', '13', 'Bidysagar Sisuniketon', 'Bengali', 'Muslim', '150000', 80, 60, 50, 65, 70, 85, 410, ''),
(28, 'Suma Monadal', 'female', 'Kamala Mondal', 'Asis Monada', 'Kolkata-80', 700019, 'suma90@gmail.com', '9593652514', '2005-06-14', '13', 'Saroda Bidhapit', 'Bengali', 'Muslim', '150000', 63, 55, 50, 52, 60, 65, 345, '2018-05-07 21:05:47');

-- --------------------------------------------------------

--
-- Table structure for table `class_six`
--

CREATE TABLE `class_six` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_six`
--

INSERT INTO `class_six` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(28, 'Selim khan', 'male', 'Sambabu khan', 'Rehana Bibi', 'Simlapal,bankura', 722151, 'selim01@gmail.com', '9800745820', '1998-05-02', '17', 'Simlapal high school', 'Bengali', 'Muslim', '36000/year', 55, 49, 59, 77, 67, 66, 373, ''),
(29, 'Rakib Khan', 'male', 'Faruk Khan ', 'Jorina Bibi', 'Simlapal ,Bankura', 722151, 'rakibkhan@gmail.com', '7001987544', '1997-05-21', '20', 'Vidyasagar Shishu Niketan High School', 'English', 'Muslim', '50000', 50, 55, 58, 48, 55, 68, 334, ''),
(30, 'Rinking Das', 'male', 'Sumon Das', 'Riya Das', 'Bishnupur,Bankura ', 722122, 'rinking784@gmail.com', '7001542655', '1998-10-20', '20', 'Bishnupur High School', 'English', 'Muslim', '55000', 76, 55, 95, 60, 76, 66, 428, ''),
(33, 'Sk abarar', 'male', 'Sk robi', 'Monira begum', 'Mohisda', 721150, 'sk2@gmail.com', '9647348652', '2007-03-06', '11', 'Majuhati prathamik school', 'Bengali', 'Muslim', '65000', 65, 98, 85, 45, 48, 47, 388, '2018-05-09 11:16:02'),
(34, 'Animesh das', 'male', 'Bapon das', 'Monika das', 'garaipur', 721150, 'animesh@gmail.com', '9647348645', '2007-02-05', '11', 'Garaipure prathamik school', 'Bengali', 'Muslim', '45000', 78, 45, 48, 46, 41, 74, 332, '2018-05-09 11:25:04'),
(35, 'Piu khatun', 'female', 'Akter sk', 'najma begum', 'Majurhati', 721150, 'piu2@gmail.com', '9647348641', '2008-05-10', '10', 'majurhati prathamik school', 'Bengali', 'Muslim', '65000', 65, 69, 63, 62, 61, 64, 384, '2018-05-09 11:31:00'),
(36, 'Rakesh mondol', 'male', 'Robin mondal', 'Sona mondal', 'Majurhati', 721150, 'rakesh2@gmail.com', '9647348653', '2007-08-19', '11', 'majurhati prathamik school', 'Bengali', 'Muslim', '48000', 64, 65, 69, 70, 80, 90, 438, '2018-05-09 11:36:39'),
(37, 'Monika sarma', 'female', 'Gulu sarma', 'Mona sarma', 'Majurhati', 721150, 'monika2@gmail.com', '7797257621', '2007-06-12', '11', 'majurhati prathamik school', 'Bengali', 'Muslim', '48000', 78, 48, 95, 64, 45, 47, 337, '2018-05-09 11:43:11'),
(38, 'kbb', 'male', 'kjbkj', 'jkhbkjh', 'kjjhjh', 123456, 'jkhjh', '1234567899', '2018-12-31', '45', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '50000', 60, 60, 60, 60, 60, 60, 360, '2018-07-23 05:46:37');

-- --------------------------------------------------------

--
-- Table structure for table `class_ten`
--

CREATE TABLE `class_ten` (
  `id` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `gender` varchar(25) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pin` int(8) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `age` varchar(10) NOT NULL,
  `school` varchar(255) NOT NULL,
  `tong` varchar(255) NOT NULL,
  `religian` varchar(100) NOT NULL,
  `income` varchar(100) NOT NULL,
  `bang` int(3) NOT NULL,
  `eng` int(3) NOT NULL,
  `math` int(3) NOT NULL,
  `phy` int(3) NOT NULL,
  `geo` int(3) NOT NULL,
  `his` int(3) NOT NULL,
  `total` int(10) NOT NULL,
  `date_time` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_ten`
--

INSERT INTO `class_ten` (`id`, `sname`, `gender`, `fname`, `mname`, `address`, `pin`, `mail`, `mobile`, `dob`, `age`, `school`, `tong`, `religian`, `income`, `bang`, `eng`, `math`, `phy`, `geo`, `his`, `total`, `date_time`) VALUES
(26, 'Payen Khatun', 'female', 'Santu Gayen ', 'Alladi Bibi', 'krishnapur', 721127, 'payael@gmail.com', '9832001213', '2006-02-25', '12', 'Chasibar Parimary school', 'Bengali', 'Muslim', '5000', 60, 30, 50, 40, 50, 60, 500, ''),
(27, 'Muktar Khan', 'male', 'Jakir Khan', 'Jorina Bibi', 'Machatora', 722151, 'muktar145@gmail.com', '9800356245', '1997-01-05', '19', 'Laxmisagar High School', 'Bengali', 'Muslim', '36000/year', 58, 55, 71, 58, 65, 47, 354, ''),
(28, 'Kamal Hussain', 'male', 'Bimal Hussain', 'Sahara Begam ', 'Kolkata-79', 700019, 'kamal@gmail.com', '8545748596', '2004-05-08', '16', 'Radha Nath School', 'Bengali', 'Muslim', '20000', 50, 45, 45, 50, 60, 70, 320, '2018-05-07 20:40:15'),
(29, 'Susmita Signa', 'female', 'Subroto Signa', 'Subroto Signa', 'Medinipore', 722166, 'susmita142gmai.com', '9732142578', '2002-06-02', '16', 'Gopinath School', 'Bengali', 'Muslim', '60000', 64, 52, 50, 53, 60, 65, 344, '2018-05-07 21:17:46'),
(30, 'Sunaina Khatun', 'female', 'Siral Rahman', 'Sonali Begum', 'Hoogly,Arambagh', 722155, 'sunaina12@gmail.com', '8001968545', '2002-01-11', '16', 'Aramabagh Primary School', 'Bengali', 'Muslim', '60000', 70, 55, 55, 50, 70, 75, 375, '2018-05-11 07:49:55'),
(31, 'Jisan Gayen', 'male', 'Samsul gayen ', 'Samsul gayen ', 'KRISHNAPUR, CHANDRAKONA', 721127, 'jisan@gmail.com', '8145680602', '2000-04-01', '18', 'Krishnapur Rahamaniya High school', 'Bengali', 'Muslim', '50000', 70, 74, 60, 62, 80, 84, 430, '2018-07-14 10:57:06'),
(32, 'kkkkkk', 'male', 'kkkk', 'kkk', 'kkk', 0, 'kkkkkkkkkk', 'kkkkkkkkkk', '2019-02-02', '52', 'Krishnapur Rahamaniya High school', 'English', 'Muslim', '50000', 65, 78, 85, 96, 74, 85, 483, '2018-07-23 05:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `library_details`
--

CREATE TABLE `library_details` (
  `id` int(11) NOT NULL,
  `sname` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `roll` varchar(255) NOT NULL,
  `bookname` varchar(255) NOT NULL,
  `authorname` varchar(255) NOT NULL,
  `withdroldate` varchar(255) NOT NULL,
  `submisiondate` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `library_details`
--

INSERT INTO `library_details` (`id`, `sname`, `class`, `roll`, `bookname`, `authorname`, `withdroldate`, `submisiondate`) VALUES
(1, 'Suraj Sing', 'viii', '05', 'Math', 'govt', '2018-09-26', '2018-10-26');

-- --------------------------------------------------------

--
-- Table structure for table `library_log`
--

CREATE TABLE `library_log` (
  `id` int(1) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `library_log`
--

INSERT INTO `library_log` (`id`, `mail`, `pass`) VALUES
(1, 'library@outlook.com', 'library');

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

CREATE TABLE `notice` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `dat` varchar(100) NOT NULL,
  `contain` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `notice`
--

INSERT INTO `notice` (`id`, `title`, `dat`, `contain`) VALUES
(2, 'Admission', '2018-05-21', 'A notification of admission letter is a letter written to inform a particular student that he or she has been selected and approved of for gaining admission in a particular institution. Admission notification letters are typically written to college-going students, that is, students who are looking to start college or to study in a new college. Such letters are congratulatory letters and are a lot like college acceptance letters. However, admission letters also inform the students of more important details and are more formal than college acceptance letters.'),
(3, 'Test', '2018-05-21', ' A notification of admission letter is a letter written to inform a particular student that he or she has been selected and approved of for gaining admission in a particular institution. Admission notification letters are typically written to college-going students, that is, students who are looking to start college or to study in a new college. Such letters are congratulatory letters and are a lot like college acceptance letters. However, admission letters also inform the students of more important details and are more formal than college acceptance letters." '),
(4, 'Result', '2018-05-21', ' A notification of admission letter is a letter written to inform a particular student that he or she has been selected and approved of for gaining admission in 2018-05-10 to 2018-06-05 particular institution. Admission notification letters are typically written to college-going students, that is, students who are looking to start college or to study in a new college. Such letters are congratulatory letters and are a lot like college acceptance letters. However, admission letters also inform the students of more important details and are more formal than college acceptance letters." ');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `class` varchar(20) NOT NULL,
  `roll` varchar(10) NOT NULL,
  `report` longtext NOT NULL,
  `date` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`id`, `student_name`, `class`, `roll`, `report`, `date`) VALUES
(2, 'Faruk Khan', 'Ten', '32', 'Please Update My Birthday 10-06-2018.', '2018-05-14'),
(3, 'jisan', '10th', '2', 'sir please currection my name\r\nnow name is "JISAN" but mistake "JISAM"\r\ncheck this; ', '2018-07-14'),
(4, 'Sarfaroj Gayen ', '10th', '5', 'I am sarfaroj Gayen', '2018-07-23');

-- --------------------------------------------------------

--
-- Table structure for table `result_eight`
--

CREATE TABLE `result_eight` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_eight`
--

INSERT INTO `result_eight` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 50, 50, 56, 52, 46, 52, 320),
('3', 'pppp', '2018-01-01', 'uu', 60, 65, 55, 50, 60, 70, 360);

-- --------------------------------------------------------

--
-- Table structure for table `result_five`
--

CREATE TABLE `result_five` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_five`
--

INSERT INTO `result_five` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 65, 60, 56, 52, 65, 52, 350),
('1', 'Abhisekb  mondal', '1994-06-06', 'Voimik Mondal', 50, 60, 20, 56, 14, 62, 352),
('32', 'Sakil Khan', '2018-05-17', 'Abu Khan ', 80, 65, 50, 60, 65, 70, 390),
('44', 'Keya de', '2008-05-10', 'Abhik de', 50, 60, 65, 57, 65, 57, 354),
('32', 'Sakil Khan', '2018-05-17', 'Abu Khan ', 80, 65, 50, 60, 65, 70, 390),
('32', 'Sakil Khan', '2018-05-17', 'Abu Khan ', 80, 65, 50, 60, 65, 70, 390),
('35', 'Asis Mitra', '2006-02-07', 'Mohima', 80, 85, 0, 0, 0, 0, 165),
('32', 'Sakil Khan', '2018-05-17', 'Abu Khan ', 80, 65, 50, 60, 65, 70, 390);

-- --------------------------------------------------------

--
-- Table structure for table `result_nine`
--

CREATE TABLE `result_nine` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_nine`
--

INSERT INTO `result_nine` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 50, 50, 56, 52, 46, 52, 320),
('1', 'abhishek kumar', '2018-05-14', 'ret', 20, 50, 10, 25, 40, 60, 205),
('4', 'kkkkk', '2019-02-03', 'kkkkkk', 50, 52, 0, 0, 0, 0, 102);

-- --------------------------------------------------------

--
-- Table structure for table `result_seven`
--

CREATE TABLE `result_seven` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_seven`
--

INSERT INTO `result_seven` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 50, 50, 56, 52, 46, 52, 320),
('1', 'abhishek kumar', '', 'dfgdfh', 70, 50, 65, 56, 50, 86, 377);

-- --------------------------------------------------------

--
-- Table structure for table `result_six`
--

CREATE TABLE `result_six` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_six`
--

INSERT INTO `result_six` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 50, 50, 56, 52, 46, 52, 320),
('34', 'Animesh das', '2007-02-05', 'Bapon das', 0, 0, 0, 0, 0, 0, 0),
('28', 'Selim khan', '1998-05-02', 'Sambabu khan', 60, 55, 85, 60, 80, 60, 400),
('33', 'Sk abarar', '2007-03-06', 'Sk robi', 50, 0, 0, 0, 0, 0, 0),
('30', 'Rinking Das', '1998-10-20', 'Sumon Das', 80, 40, 0, 0, 0, 0, 120),
('28', 'Selim khan', '1998-05-02', 'Sambabu khan', 60, 55, 85, 60, 80, 60, 400);

-- --------------------------------------------------------

--
-- Table structure for table `result_ten`
--

CREATE TABLE `result_ten` (
  `id` varchar(10) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `bengali` int(10) NOT NULL,
  `english` int(10) NOT NULL,
  `math` int(10) NOT NULL,
  `phy` int(10) NOT NULL,
  `geo` int(10) NOT NULL,
  `his` int(10) NOT NULL,
  `total` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `result_ten`
--

INSERT INTO `result_ten` (`id`, `sname`, `dob`, `fname`, `bengali`, `english`, `math`, `phy`, `geo`, `his`, `total`) VALUES
('37', 'sk sabir', '2008-03-01', 'sk abir', 45, 50, 60, 60, 45, 85, 500),
('31', 'Faruk khan', '1998-04-20', 'Siraj khan', 50, 50, 56, 52, 46, 52, 320),
('29', 'Susmita Signa', '2002-06-02', 'Subroto Signa', 20, 25, 58, 60, 85, 87, 335),
('26', 'Payen Khatun', '2006-02-25', 'Santu Gayen ', 50, 60, 0, 0, 0, 0, 110);

-- --------------------------------------------------------

--
-- Table structure for table `student_admission`
--

CREATE TABLE `student_admission` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `mname` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `bengali` varchar(255) NOT NULL,
  `english` varchar(255) NOT NULL,
  `math` varchar(255) NOT NULL,
  `geo` varchar(255) NOT NULL,
  `life` varchar(255) NOT NULL,
  `physics` varchar(255) NOT NULL,
  `Totalno` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dob` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `student_admission`
--

INSERT INTO `student_admission` (`id`, `name`, `fname`, `mname`, `gender`, `bengali`, `english`, `math`, `geo`, `life`, `physics`, `Totalno`, `email`, `dob`, `address`) VALUES
(33, 'kousik panda', 'gopal panda', 'rina panda', 'Male', 'Bengali', 'English', 'Mathemetics', 'Geograghy', 'Lifescience', 'physicalscience', '0', 'kopanda8972@gmail.com', '1994-06-29', 'jhargram'),
(41, 'Sarfaroj Gayen ', 'Samsul gayen ', 'Tuhina Gayen ', 'Male', '80', '60', '50', '75', '80', '65', '450', 'sarfaroj@gmail.com', '1995-04-10', 'krishnapur');

-- --------------------------------------------------------

--
-- Table structure for table `t_reg`
--

CREATE TABLE `t_reg` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tdeperment` varchar(255) NOT NULL,
  `tsubject` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `t_reg`
--

INSERT INTO `t_reg` (`id`, `name`, `tdeperment`, `tsubject`, `sex`, `age`, `mail`, `pass`, `address`) VALUES
(17, 'Subhas Manna', 'Science', 'Physic', 'Male', '38', 'subhas_sci@gmail.com', 'subhas', 'Ghatal'),
(18, 'Bandana Samanta', 'Arts', 'Bengali', 'Male', '37', 'bandana_ben@gmail.com', 'bandana', 'Arambagh'),
(19, 'Kalicharan Rana', 'Arts', 'English', 'Male', '32', 'kalicharan_eng@gmail.com', 'kalicharan', 'Katulpur'),
(20, 'Suman Das', 'Science', 'Mathametics', 'Male', '36', 'suman_math@gmail.com', 'suman', 'Daspur'),
(21, 'Monika Roy', 'Arts', 'Geography', 'Male', '34', 'monika_geo@gmail.com', 'monika', 'Hoogly'),
(22, 'Subroto Samanto', 'Head Teacher', 'History', 'Male', '40', 'subroto_geo@gmail.com', 'subroto', 'Chandrakona'),
(23, 'Surya', 'CSE', 'CS', 'Male', '21', 'sarfaroj95@gmail.com', 'Surya', 'KRISHNAPUR, CHANDRAKONA'),
(24, 'SARFAROJ GAYEN', 'Science', 'Mathametics', 'Male', '24', 'sarfaroj95@gmail.com', 'sarfaroj', 'KRISHNAPUR, CHANDRAKONA');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_login`
--
ALTER TABLE `admin_login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_eight`
--
ALTER TABLE `class_eight`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_five`
--
ALTER TABLE `class_five`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_nine`
--
ALTER TABLE `class_nine`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_seven`
--
ALTER TABLE `class_seven`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_six`
--
ALTER TABLE `class_six`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_ten`
--
ALTER TABLE `class_ten`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_details`
--
ALTER TABLE `library_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_log`
--
ALTER TABLE `library_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_admission`
--
ALTER TABLE `student_admission`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_reg`
--
ALTER TABLE `t_reg`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `class_eight`
--
ALTER TABLE `class_eight`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `class_five`
--
ALTER TABLE `class_five`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
--
-- AUTO_INCREMENT for table `class_nine`
--
ALTER TABLE `class_nine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `class_seven`
--
ALTER TABLE `class_seven`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `class_six`
--
ALTER TABLE `class_six`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT for table `class_ten`
--
ALTER TABLE `class_ten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
--
-- AUTO_INCREMENT for table `library_details`
--
ALTER TABLE `library_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `library_log`
--
ALTER TABLE `library_log`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `notice`
--
ALTER TABLE `notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `student_admission`
--
ALTER TABLE `student_admission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
--
-- AUTO_INCREMENT for table `t_reg`
--
ALTER TABLE `t_reg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
