<?php

use think\Loader;


/**

 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @author static7  */
function excelExport($fileName = '', $headArr = [], $data = [], $widths=[]) {
    $fileName = iconv("UTF-8", "GB2312//IGNORE", @$fileName);
    $fileName .=".xlsx";
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties();
    $ordA = ord('A'); //65
    $key2 = ord("@"); //64
    foreach ($headArr as $v) {
        if($ordA > ord("Z"))
        {
            $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
        }else{
            $colum = chr($ordA++);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
    }
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach ($data as $key => $rows) { // 行写入
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($rows as $keyName => $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->setCellValue($colum . $column, $value);
        }
        $column++;
    }
    //表格宽度
    if(count($widths)){
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($widths as  $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->getColumnDimension($colum)->setWidth($value);
        }
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
    header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
    header('Cache-Control: max-age=0');//禁止缓存
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
//    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//    //$objWriter->save('./excelFile/'.$fileName);
//    header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
//    header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
//    header('Cache-Control: max-age=0');//禁止缓存
//    $objWriter->save("php://output");
}