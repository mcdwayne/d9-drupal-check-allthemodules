<?php
/**
 * @file
 * Contains \Drupal\who_bought_what\Controller\who_bought_whatController.
 */
namespace Drupal\who_bought_what\Controller;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;


class who_bought_whatController {
  public $export;
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('who_bought_what, World!'),
    );
  }
  
  public function buyers() {
    return array(
      '#type' => 'markup',
      '#markup' => t('who_bought_what, Buyers!'),
    );
  }
  
  public function buyers2() {
    return array(
      '#type' => 'markup',
      '#markup' => t('who_bought_what, Buyers!'),
    );
  }
  
  public function getwbwtitle($pid){
    $product = Node::load($pid);
	$title = 'Who Bought ' . ($product->label());
	return $title;
  }
  
  public function mailbuyers($pid) {
    
	$myProd = wbw_getvals($pid);

	$form = array();
	return $form;  
  }
  
  
  
  public function whobought($pid) {
    
	// Get the current user
    $user = \Drupal::currentUser();

    // Check for permission
    if(!$user->hasPermission('view reports') && !$user->hasPermission('view who bought what')){
	  throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
	}
    
	$myProd = wbw_getvals($pid);

	$form = array();
	
	$form['intro'] =array(
      '#type' => 'markup',
      '#markup' => t('Buyers of ' . $myProd['title'] . 
	  ".  There are {$myProd['qty']} sold in {$myProd['ordct']} orders for a total of " .uc_currency_format($myProd['gross']) .
	  "<br><a href = \"/admin/store/who-bought/$pid/csv\">Download a CSV file</a>"),
      );	
	  

	
	
	$form['buyers'] = array(
  '#type' => 'table',
  '#caption' => t('Who Bought'),
  '#header' => $myProd['headers'],
   );
   $i=0;
   foreach($myProd['rows'] as $row) {
     
	 
     $i++;
     foreach($row as $key=>$field){
        $form['buyers'][$i][$key] = array(
          '#markup' => $field,    
        );
      }
	 $form['buyers'][$i]['primary_email'] = array( 
	   '#markup' => "<a href = \"MAILTO: {$row['primary_email']}\">{$row['primary_email']}</a>",
	 );
	 $form['buyers'][$i]['n_last'] = array( 
	   '#markup' => "<a href = \"/users/{$row['uid']}\">{$row['n_last']}</a>",
	 );
	 $form['buyers'][$i]['oid'] = array( 
	   '#markup' => "<a href = \"/admin/store/orders/{$row['oid']}\">{$row['oid']}</a>",
	 );

  }

  return $form;   
 }
  
  public function who_bought_csv($pid){

    $export = '';
    $fp = fopen('php://memory', 'rw');
	$vals = wbw_getvals($pid, TRUE);

    $to_export = $vals['headers'];
    fputcsv($fp, $to_export);

    foreach ($vals['rows'] as $row) {
        $to_export = $row;
		fputcsv($fp, $to_export);
      }
      
      
    
    rewind($fp);
    while (!feof($fp)) {
      $this->export .= fread($fp, 8192);
	  
    }
	$myProd = wbw_getvals($pid);
	$filename = 'who_bought_' . urlencode(strtolower($myProd['title'])) . '.csv';
	
    fclose($fp);
	$response = new Response();
	$response->setStatusCode(200);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->setContent($this->export);
    return $response;

  }

}

function wbw_report_statuses(){
  return array(
    'pending',
    'processing',
    'payment_received',
    'completed');
}


function wbw_getvals($pid, $csv = FALSE){
  $vals = array();
  $product = Node::load($pid);
  $vals['title'] = ($product->label());
  
  $data = array();
  $orderFields = array('qty', 'model',   'n_last','n_first', 'price', 'total', 'oid', 'Order_Date', 'status', 'primary_email', 'payment_method' );
  $orderFields2 = array('billing_phone', 'delivery_first_name', 'delivery_last_name', 
	'delivery_phone', 'uid');
  if($csv){
    $orderFields2 = array_merge($orderFields2, array( 'billing_company', 'billing_street1', 'billing_street2', 'billing_city', 
	'billing_zone', 'billing_postal_code', 'billing_country', 
	'delivery_first_name', 'delivery_last_name', 'delivery_phone', 'delivery_company', 'delivery_street1', 'delivery_street2', 
	'delivery_city', 'delivery_zone', 'delivery_postal_code', 'delivery_country'));
  }
	
	//now get the attributes
  $attr = uc_attribute_load_product_attributes($pid);
  foreach($attr as $attribute){
	  $data[] = ($attribute->label == '<none>')?$attribute->name:$attribute->label;
	}
  $vals['headers'] = array_merge($orderFields, $data, $orderFields2);
  
  $sql = "SELECT `order_status` AS status, `model`, `primary_email`, `delivery_first_name`, uid, `delivery_last_name`, 
	`billing_first_name` AS n_first, `billing_last_name` AS n_last,
    `payment_method`, `created` AS Order_Date, ucop.`order_id` AS oid, `title`, 
	`model`, `qty`, `price`, ucop.`data`, qty*price AS total, " .
	
	implode(', ', $orderFields2) .
	
	" FROM {uc_order_products} ucop 
	JOIN {uc_orders} uco ON uco.order_id = ucop.order_id
	 WHERE nid = :pid AND order_status IN ('pending',
    'processing',
    'payment_received',
    'completed')";//
	
	$result = db_query($sql, array(':pid' => $pid));
	
    $rows = array();
	$qty = 0;
	$ordct = 0;
	$gross = 0;
	
	foreach($result as $record){
	
	  $ordct++; // increment the order count
	  $qty += $record->qty;
	  $gross += $record->total;
	
	  $record->price = uc_currency_format($record->price);
	  $record->total = uc_currency_format($record->total);
	  $record->Order_Date = format_date($record->Order_Date, 'html_date');
	
	  $row = array();
	  foreach($orderFields as $field){
	    $row[$field] = isset($record->$field) ? $record->$field:'';
	  }
	  $rowAttrs = unserialize($record->data);
	  foreach($data as $field){
	    $row[$field] = isset($rowAttrs['attributes'][$field]) ? implode('; ',$rowAttrs['attributes'][$field] ): '';
	  }
	  foreach($orderFields2 as $field){
	    $row[$field] = isset($record->$field) ? $record->$field:'';
	  }
	  $rows[]=$row;

	}
	$vals['rows'] = $rows;
	$vals['ordct'] = $ordct;
	$vals['qty'] = $qty;
	$vals['gross'] = $gross;
  
  return $vals;
}

