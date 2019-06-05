<?php session_start();
error_reporting(0);?>
<?php
include("mpdf/mpdf.php");
//require("../../../vendor/autoload.php");
require("../../core/config.core.php");
require("../../core/connect.core.php");
require("../../core/functions.core.php");
$getdata = new clear_db();
$connect = $getdata->my_sql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME);
$getdata->my_sql_set_utf8();

use mPDF;
$mpdf = new mPDF('th', 'A4-L', '0', 'THSaraban');
$head = '
<style>
	body{
		font-family: "Garuda";//เรียกใช้font Garuda สำหรับแสดงผล ภาษาไทย
	}
  table {
  border-collapse: collapse;
  width: 100%;
}

th {
  text-align: center;
  padding: 8px;
}

td {
  text-align: left;
  padding: 8px;
}

tr:nth-child(even){background-color: #f2f2f2}

th {
  background-color: #465246;
  color: white;
}
.footer {
  font-size: 14px;
   position: fixed;
   left: 0;
   bottom: 0;
   width: 100%;
   color: black;
   text-align: right;
}
</style>

<b> </b>
<h2 style="text-align:center">รายงานรับสินค้าเข้า</h2>
<p style="text-align:center"><b>ร้านล้อยางไว้ใจผม</b></p>
<p style="text-align:center"><b>รายงาน ณ วันที่ '.dateConvertor(date("Y-m-d")).' </b></p>';
$str_sql = "";
if(htmlentities($_GET['from']) && htmlentities($_GET['to'])){
		$str_sql  .= "mm.datedo BETWEEN '".htmlentities($_GET['from'])."' and '".htmlentities($_GET['to'])."' ";
    $head .= '<p style="text-align:center"><b>วันที่รับสินค้าเข้า ระหว่าง : '.dateConvertor($_GET['from']).' ถึง วันที่ : '.dateConvertor($_GET['to']).' </b></p>';

}
$head .= '<table>
    <tr>
		<th width="5%">ลำดับ</th>
		<th width="10%">รหัสสินค้า </th>
		<th width="30%" style="text-align: center;">รายละเอียด </th>
		<th width="10%" align="left">ผู้จำหน่าย</th>
		<th width="10%" align="left">ผู้รับ</th>
		<th width="10%">จำนวน</th>
		<th width="10%">ราคา</th>
    </tr>
</thead>';


$DetailProduct = $getdata->my_sql_select("mm.iduser,mm.dealer_code,m.ProductID,m.dealer_code,sum(m.total) as total,sum(m.price * m.total) as price "
																				," stock_tb_receive_master_sub m
																				left join stock_tb_receive_master mm on mm.po = m.po "
																			 ," $str_sql Group by m.ProductID,m.dealer_code,mm.iduser,mm.dealer_code ORDER BY  m.ProductID ");
													if(mysql_num_rows($DetailProduct) > 0){
												 	 $sumbyproductID = 0;
												 	 $sumamt1=0;
												 	 $i = 1;
												 	 while($showDetailProduct = mysql_fetch_object($DetailProduct)){

										            $Detail = $getdata->my_sql_query("p.*, r.*, w.* ,w.diameter as diameterWheel,r.diameter as diameterRubber,p.ProductID as ProductID,r.diameter as rubdiameter ,w.diameter as whediameter,w.gen as genWheel
																,case
																	when p.TypeID = '2'
																	then (select b.Description from brandRubble b where r.brand = b.id)
																	when p.TypeID = '1'
																	then (select b.Description from BrandWhee b where b.id = w.brand)
																	end BrandName
																	,case
																		when p.TypeID = '2'
																		then (select r.code from productdetailrubber r where r.ProductID = p.ProductID)
																		when p.TypeID = '1'
																		then (select w.code from productdetailwheel w where w.ProductID = p.ProductID)
																		end code
																"
																," product_N p
																left join productDetailWheel w on p.ProductID = w.ProductID
																left join productdetailrubber r on p.ProductID = r.ProductID "
																," p.ProductID = '".$showDetailProduct->ProductID."'  Order by p.ProductID ");

																	$sumamt1 = $sumamt1 + $showDetailProduct->total;
																	$sumbyproductID = $sumbyproductID + $showDetailProduct->price;


										                    if($Detail->TypeID == '1'){
										                      $gettype = $Detail->BrandName." รุ่น:".$Detail->genWheel." ขนาด:".$Detail->rimm." ขอบ:".$Detail->whediameter." รู:".$Detail->holeSize." ประเภท:".$Detail->typeFormat;
										                    }else if($Detail->TypeID == '2'){
										                      $gettype = $Detail->BrandName." ขนาด:".$Detail->diameterRubber." ซี่รี่:".$Detail->series." ความกว้าง:".$Detail->width;
										                    }else{
										                      $gettype = "";
										                    }

										                $content .='<tr>
																			<td align="center"><strong>'.@$i.'</strong></td>
										                  <td align="center"><strong>'.@$Detail->code.'</strong></td>
										                  <td><strong>'.@$gettype.'</strong></td>
																			<td valign="middle" style=" text-align: left;"><strong>'.@$showDetailProduct->dealer_code.'</strong></td>
																			<td valign="middle" style=" text-align: left;"><strong>'.@$showDetailProduct->iduser.'</strong></td>
										                  <td valign="middle" style=" text-align: center;"><strong>'.@convertPoint2($showDetailProduct->total,'0').'&nbsp;ชิ้น</strong></td>
										                  <td valign="middle" style=" text-align: right;"><strong>'.@convertPoint2(@$showDetailProduct->price,'2').'&nbsp;</strong></td>
										                </tr>';
																		$i ++;
 																	}
																}

																$content .='<tr style="text-align: right; background:#525050;">
																<td style="color: white; text-align: right;" colspan="5">รวมจำนวน/ใบรับสินค้า</td>
																<td valign="middle" style=" text-align: center; color: white;"><strong>'.@convertPoint2($sumamt1,'0').'&nbsp;ชิ้น</strong></td>
																<td valign="middle" style=" text-align: right; color: white;"><strong>'.@convertPoint2($sumbyproductID,'2').'</strong></td>
																</tr>';





$content .= '<tbody>';

$end = '</tbody>
</table>
<div class="footer">
 <p style="margin-right: 10px; color: #bbb7b7;">@รายงานร้านยางไว้ใจผม</p>
</div>';



$mpdf->WriteHTML($head);
$mpdf->WriteHTML($content);
$mpdf->WriteHTML($end);

$mpdf->Output();
?>
