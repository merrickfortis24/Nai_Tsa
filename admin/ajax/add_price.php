<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once('../classes/database.php');
$db = new database();

if($_SERVER['REQUEST_METHOD']!=='POST'){ echo json_encode(['success'=>false,'message'=>'Invalid request method']); exit; }

$price_amount = trim($_POST['price_amount'] ?? '');
$effective_from = trim($_POST['effective_from'] ?? '');
$effective_to = trim($_POST['effective_to'] ?? '');

if($price_amount === '' || !is_numeric($price_amount) || $price_amount <= 0){ echo json_encode(['success'=>false,'message'=>'Enter a valid positive price amount']); exit; }
if($effective_from === ''){ echo json_encode(['success'=>false,'message'=>'Effective From is required']); exit; }

function _validDate($d){ if(!$d) return false; $dt=DateTime::createFromFormat('Y-m-d',$d); return $dt && $dt->format('Y-m-d')===$d; }
if(!_validDate($effective_from)){ echo json_encode(['success'=>false,'message'=>'Invalid Effective From date']); exit; }
if($effective_to !== '' && !_validDate($effective_to)){ echo json_encode(['success'=>false,'message'=>'Invalid Effective To date']); exit; }
if($effective_to !== '' && $effective_to < $effective_from){ echo json_encode(['success'=>false,'message'=>'Effective To cannot be before Effective From']); exit; }

$res = $db->addPrice(number_format((float)$price_amount,2,'.',''), $effective_from, $effective_to ?: null);
if(!$res['success']){ echo json_encode($res); exit; }

// Refresh price list for immediate dropdown update
try { $prices = $db->getAllPrices(); } catch(Throwable $e){ $prices = []; }
echo json_encode(['success'=>true,'message'=>'Price added successfully','prices'=>$prices]);