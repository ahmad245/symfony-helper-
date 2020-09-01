<?php

namespace UfmcpBundle\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Yectep\PhpSpreadsheetBundle\Factory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\Response;
use UfmcpBundle\Types\DateTime;

use PhpOffice\PhpSpreadsheet\IOFactory;

use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;

class ExcelGenerator {
    /**
     * @var Factory
     */
    private $phpSpreadsheet;
    
    /**
     * @var Utils
     */
    private $utils;
    
    /**
     * ExcelGenerator constructor.
     * @param Factory $phpSpreadsheet
     * @param Utils $utils
     */
    public function __construct(Factory $phpSpreadsheet, Utils $utils) {
        $this->phpSpreadsheet = $phpSpreadsheet;
        $this->utils = $utils;
    }
    
    /**
     * @param $data
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    public function generateXls($data) {
    if(is_array($data)) {
        $data = json_decode(json_encode($data));
    }
    $spreadsheet = $this->phpSpreadsheet->createSpreadsheet();
    $sheet = $spreadsheet->getActiveSheet();


    $header_style = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'ffffff']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '34bcbd']
        ],
        'borders' => [
            'left' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'ffffff'],
            ],
            'right' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'ffffff'],
            ],
        ],
    ];

    /**
     * formaté de la même manière que $header_style
     * provient de tablesorter.init.js
     * dans la vue :
     *  => alignement horizontal : data-align="center"
     */
    $content_style = [];
    if(isset($data->content_style)) {
        $content_style = json_decode(json_encode($data->content_style), true);
    }

    // En-tete du tableau
    $row = 1;
    $lettre = 'A';
    $rowspanLetter = null;
    foreach($data->headers as $header) {
        if($header->row > $row-1) {
            $row++;
            $lettre = (empty($rowspanLetter) ? 'A' : $rowspanLetter);
            $rowspanLetter = null;
        }

        $sheet->setCellValue($lettre.$row, $header->text);
        $sheet->getStyle($lettre.$row)->applyFromArray($header_style);

        if(!empty($header->rowspan) && $header->rowspan > 1) {
            $sheet->mergeCells($lettre.$row.':'.$lettre.($row+($header->rowspan-1)));

            $rowspanLetter = $lettre;
            $rowspanLetter++;
        }

        if(!empty($header->colspan) && $header->colspan > 1) {
            $nextLetter = $lettre;
            for($i=0; $i < $header->colspan-1; $i++) {
                $nextLetter++;
            }

            $sheet->mergeCells($lettre.$row.':'.$nextLetter.$row);


            $lettre = $nextLetter;
            $lettre++;
        } else {
            $lettre++;
        }
    }

    $utils = $this->utils;

    // Corps => donnees
    $row++;
    foreach($data->content as $content) {
        $lettre = 'A';
        foreach($content as $iHead => $c) {
            if($c === null) {
                continue;
            }
            $c = trim($c);
            $i = ord($lettre) - ord('A');

            if(!empty($c)) {
                if(strpos($c, "[color=red]") !== false) {
                    $red = new Color(Color::COLOR_RED);
                    $sheet->getStyle($lettre . $row)->getFont()->setColor($red);
                    $c = trim(str_replace("[color=red]", "", $c));
                }
            }

            $dataFormat = isset($data->headers[$iHead], $data->headers[$iHead]->config, $data->headers[$iHead]->config->format) ? $data->headers[$iHead]->config->format : null;
            $formatted = false;

            if(!empty($dataFormat)) {
                switch($dataFormat) {
                    case "currency":
                        $c = $utils->parseFormattedNumber($c);
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);

                        if(!isset($content_style[$i]['alignment'])) {
                            $content_style[$i]['alignment'] = [
                                'horizontal' => Alignment::HORIZONTAL_RIGHT
                            ];
                        }

                        if(!isset($content_style[$i]['alignment'])) {
                            $content_style[$i]['alignment'] = [];
                        }
                        $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                        $formatted = true;
                        break;

                    case "numeric":
                        $c = $utils->parseFormattedNumber($c);

                        if (round($c) == $c) {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }

                        if(!isset($content_style[$i]['alignment'])) {
                            $content_style[$i]['alignment'] = [];
                        }
                        $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                        $formatted = true;
                        break;

                    case "kilometers":
                        $c = $utils->parseFormattedNumber($c);
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode( '#,##0.00_-"km"');

                        if(!isset($content_style[$i]['alignment'])) {
                            $content_style[$i]['alignment'] = [];
                        }
                        $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                        $formatted = true;
                        break;
                }
            }

            if(!$formatted) {
                if (is_numeric($c)) {
                    if (round($c) == $c) {
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                    } else {
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                    $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                    $date = \DateTime::createFromFormat('d/m/Y', $c);
                    $date->setTime(0, 0, 0);
                    $c = Date::PHPToExcel($date);

                    $content_style[$i]['alignment']['horizontal'] = 'center';
                }
            }

            if(!isset($content_style[$i]['alignment'])) {
                $content_style[$i]['alignment'] = [];
            }

            if(!isset($content_style[$i]['alignment']['horizontal']) || $content_style[$i]['alignment']['horizontal'] == 'left') {
                $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
            } else if(isset($content_style[$i]['alignment']['horizontal']) && $content_style[$i]['alignment']['horizontal'] == 'center') {
                $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
            }

            $sheet->setCellValue($lettre.$row, $c);
            if(isset($content_style[$i])) {
                $sheet->getStyle($lettre . $row)->applyFromArray($content_style[$i]);
            }
            $sheet->getColumnDimension($lettre)->setAutoSize(true);
            $lettre++;
        }
        $row++;
    }

    //$writer->setActiveSheetIndex(0);
    $response = $this->phpSpreadsheet->createStreamedResponse($spreadsheet, 'Xlsx');
    $dispositionHeader = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        (isset($data->title) ? $data->title : 'export').'.xlsx'
    );
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Cache-Control', 'maxage=1');
    $response->headers->set('Content-Disposition', $dispositionHeader);

    return $response;
}
    public function generateXls2($data,$data2,$jourOut) {
        if(is_array($data)) {
            $data = json_decode(json_encode($data));
        }
        if(is_array($data2)) {
            $data2 = json_decode(json_encode($data2));
        }
        $spreadsheet = $this->phpSpreadsheet->createSpreadsheet();
        $spreadsheet2 = $this->phpSpreadsheet->createSpreadsheet();

        $sheet = $spreadsheet->getActiveSheet();



        $header_style = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'alignmentRight' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFFF00']
            ],

            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $header_style2 = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0000000']
            ],
            'fontSemaine' => [
                'bold' => true,
                'color' => ['rgb' => '548235']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'BDD7EE']
            ],
            'fill2' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'a5a4a4']
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],

                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'borders2' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];


        $content_style = [];
        if(isset($data->content_style)) {
            $content_style = json_decode(json_encode($data->content_style), true);
        }

        // En-tete du tableau
        $row = 1;
        $lettre = 'A';


        $rowspanLetter = null;
        $sheet->mergeCells('A1:E2');
        $sheet->mergeCells('F1:R2');


        for ($y =1 ; $y <13; $y++) {
            $teteP='A';
            for ($x = 0; $x <18; $x++) {
                $sheet->getStyle($teteP.$y)->getBorders()->applyFromArray($header_style2['borders2']);
                $teteP++;
            }

        }



        foreach($data->headers as $header) {
            if($header->row > $row-1) {
                $row++;

                $lettre = (empty($rowspanLetter) ? 'A' : $rowspanLetter);
                $rowspanLetter = null;
            }

            $sheet->setCellValue($lettre.$row, $header->text);
            $sheet->getStyle($lettre.$row)->applyFromArray($header_style);


            if(!empty($header->rowspan) && $header->rowspan > 1) {
                $sheet->mergeCells($lettre.$row.':'.$lettre.($row+($header->rowspan-1)));


                $rowspanLetter = $lettre;
                $rowspanLetter++;

            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter = $lettre;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter++;
                }

               $sheet->mergeCells($lettre.$row.':'.$nextLetter.$row);



                $lettre = $nextLetter;
                $lettre++;
            } else {
                $lettre++;
            }


        }

        $row++;

        //added 25/06
        $row++;

        foreach($data->content as $content) {
            $lettre = 'A';
            $lettertest='E';
            $lettertest2='F';
            $lettertest3='R';

            foreach($content as $iHead => $c) {

                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre) - ord('A');


                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre . $row)->getFont()->setColor($red);

                        $c = trim(str_replace("[color=red]", "", $c));


                    }

                }

                $dataFormat = isset($data->headers[$iHead], $data->headers[$iHead]->config, $data->headers[$iHead]->config->format) ? $data->headers[$iHead]->config->format : null;
                $formatted = false;



                if(!$formatted) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style[$i]['alignment'])) {
                    $content_style[$i]['alignment'] = [];
                }

                if(!isset($content_style[$i]['alignment']['horizontal']) || $content_style[$i]['alignment']['horizontal'] == 'left') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style[$i]['alignment']['horizontal']) && $content_style[$i]['alignment']['horizontal'] == 'center') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }

               $sheet->mergeCells($lettre.$row.':'.$lettertest.$row);
               $sheet->mergeCells($lettertest2.$row.':'.$lettertest3.$row);
                $sheet->setCellValue($lettre.$row, $c);


                if(isset($content_style[$i])) {
                    $sheet->getStyle($lettre . $row)->applyFromArray($content_style[$i]);

                }
                $red = new Color(Color::COLOR_RED);
                $blue = new Color(Color::COLOR_BLUE);
                $sheet->getStyle('A10')->getFont()->setColor($red);
                $sheet->getStyle('A11')->getFont()->setColor($red);
                $sheet->getStyle('A12')->getFont()->setColor($red);
                $sheet->getStyle('F9')->getFont()->setColor($red);
                $sheet->getStyle('F10')->getFont()->setColor($red);
                $sheet->getStyle('F11')->getFont()->setColor($red);
                $sheet->getStyle('F12')->getFont()->setColor($red);

                $sheet->getStyle('F3')->getFont()->setColor($blue);
                $sheet->getStyle('F4')->getFont()->setColor($blue);
                $sheet->getStyle('F5')->getFont()->setColor($blue);
                $sheet->getStyle('F6')->getFont()->setColor($blue);
                $sheet->getStyle('F7')->getFont()->setColor($blue);
                $sheet->getStyle('F8')->getFont()->setColor($blue);

                $sheet->getStyle('A10')->getAlignment()->applyFromArray($header_style['alignmentRight']);
                $sheet->getStyle('A11')->getAlignment()->applyFromArray($header_style['alignmentRight']);
                $sheet->getStyle('A12')->getAlignment()->applyFromArray($header_style['alignmentRight']);

                for ($align=3;$align<13;$align++){
                    $sheet->getStyle('F'.$align)->getAlignment()->applyFromArray($header_style['alignment']);
                }


                $sheet->getColumnDimension($lettre)->setAutoSize(true);
                $sheet->getRowDimension('3')->setRowHeight(30);
                $sheet->getRowDimension('8')->setRowHeight(30);
                $sheet->getRowDimension('9')->setRowHeight(30);
                $sheet->getRowDimension('10')->setRowHeight(30);

                $sheet->getStyle($lettre . $row)->getAlignment()->setWrapText(true);






                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;



                $lettertest++;
                $lettertest2++;
                $lettertest3++;
            }


            $row++;


        }


      // die();::::::::
        $row++;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $content_style2 = [];
        if(isset($data2->content_style)) {
            $content_style2 = json_decode(json_encode($data2->content_style), true);
        }

        // En-tete du tableau
         //added 26/6//////////////////////////////////////////////////////////////////////////////////////////////
       $tete='A';
        for ($x = 0; $x <44; $x++) {
            $sheet->getStyle($tete.'14')->getBorders()->applyFromArray($header_style2['borders2']);
            $tete++;

        }






        $lettre2 = 'A';
        $rowspanLetter2 = null;
        foreach($data2->headers as $header) {

            if($header->row > $row-1) {
                $row++;
                $lettre2 = (empty($rowspanLetter2) ? 'A' : $rowspanLetter2);
                $rowspanLetter2 = null;
            }

            $sheet->setCellValue($lettre2.$row, $header->text);
            $sheet->getStyle($lettre2.$row)->applyFromArray($header_style2);

            if(!empty($header->rowspan) && $header->rowspan > 1) {
                $sheet->mergeCells($lettre2.$row.':'.$lettre2.($row+($header->rowspan-1)));

                $rowspanLetter2 = $lettre2;
                $rowspanLetter2++;
            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter2 = $lettre2;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter2++;
                }

                $sheet->mergeCells($lettre2.$row.':'.$nextLetter2.$row);

                $lettre2 = $nextLetter2;
                $lettre2++;
            } else {
                $lettre2++;
            }


        }

        $utils = $this->utils;

        // Corps => donnees
        $row++;
        $iterator=0;

        foreach($data2->content as $content) {
            $lettre2 = 'A';



            $lettreHH='B';
           // $sheet->mergeCells($lettre2.$row.':'.'B'.$row);




          //  var_dump($lettre2);


            foreach($content as $iHead => $c) {




                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre2) - ord('A');

                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor($red);
                        $c = trim(str_replace("[color=red]", "", $c));
                    }
                }

                $dataFormat2 = isset($data2->headers[$iHead], $data2->headers[$iHead]->config, $data2->headers[$iHead]->config->format) ? $data2->headers[$iHead]->config->format : null;
                $formatted2 = false;
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(8);

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                if(!empty($dataFormat2)) {
                    switch($dataFormat2) {
                        case "currency":
                            $c = $utils->parseFormattedNumber($c);
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);

                            if(!isset($content_style2[$i]['alignment'])) {
                                $content_style2[$i]['alignment'] = [
                                    'horizontal' => Alignment::HORIZONTAL_RIGHT
                                ];
                            }

                            if(!isset($content_style2[$i]['alignment'])) {
                                $content_style2[$i]['alignment'] = [];
                            }
                            $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                            $formatted2 = true;
                            break;

                        case "numeric":
                            $c = $utils->parseFormattedNumber($c);

                            if (round($c) == $c) {
                                $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                            } else {
                                $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                            }

                            if(!isset($content_style2[$i]['alignment'])) {
                                $content_style2[$i]['alignment'] = [];
                            }
                            $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                            $formatted2 = true;
                            break;

                        case "kilometers":
                            $c = $utils->parseFormattedNumber($c);
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode( '#,##0.00_-"km"');

                            if(!isset($content_style2[$i]['alignment'])) {
                                $content_style2[$i]['alignment'] = [];
                            }
                            $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;

                            $formatted2 = true;
                            break;
                    }
                }

                if(!$formatted2) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style2[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style2[$i]['alignment'])) {
                    $content_style2[$i]['alignment'] = [];
                }

                if(!isset($content_style2[$i]['alignment']['horizontal']) || $content_style2[$i]['alignment']['horizontal'] == 'left') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style2[$i]['alignment']['horizontal']) && $content_style2[$i]['alignment']['horizontal'] == 'center') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }
                ///////////////////////////////////////////////////HORIZONTAL_CENTER//////////////////////////////////////////////////////////////////////////////////////////////
                $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                if($c==1  && $iterator!==30){
                   // $sheet->getStyle($lettre2 . $row)->getFont()->setColor( new Color(Color::COLOR_DARKGREEN));
                }
                // for semaine
                if(strpos($c,'S')!==false ){
                  //  $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['fontSemaine']);
                   $sheet->getStyle($lettre2 . $row)->getFont()->setSize(12);
                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);


                }
                // for hour
                if(strpos($c,'h')!==false ){


                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(8);

                }
                // for jour
                if(is_float($c)){


                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(12);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);

                }
                //total row
                if($iHead==42){

                  //  $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(12);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                }
                // total col
                if($iterator==30){

                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                }
                // for jour out
                if(in_array($content[0],$jourOut) ){
                   // var_dump($c);
                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                }

                // border for all
                $sheet->getStyle($lettre2 . $row)->getBorders()->applyFromArray($header_style2['borders2']);
                $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


                //added 26/6
                if($iHead==0){

                    $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    // for semaine
                    if(strpos($c,'S')!==false ){
                        //  $sheet->mergeCells('A'.$row.':'.'B'.$row);
                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor( new Color(Color::COLOR_DARKGREEN));
                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);
                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);

                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);


                    }
                    if(is_float($c)){


                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                        $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);

                    }
                    if(isset($content_style2[$i])) {
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                    }
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                    // add border to B letter
                    $sheet->getStyle('B' . $row)->getBorders()->applyFromArray($header_style2['borders2']);

                    $sheet->setCellValue($lettre2.$row, $c);
                    if(in_array($content[0],$jourOut) ){
                        // var_dump($c);
                        $sheet->setCellValue($lettre2.$row, '');
                    }
                    $lettre2++;


                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


                  $sheet->setCellValue($lettre2.$row, $c);






                if(isset($content_style2[$i])) {
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                }
                $sheet->getColumnDimension($lettre2)->setAutoSize(true);
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(5);
                $lettre2++;




            }

            $row++;
            $iterator++;

        }
        $sheet->getStyle('A14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('B14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFont()->setSize(14);
        $sheet->getStyle('A14')->getFont()->setSize(14);
        $sheet->getStyle('C14')->getFont()->setSize(14);


        for ($i=14 ;$i<=45;$i++){
            $sheet->getStyle('AR'.$i)->getFill()->applyFromArray($header_style2['fill2']);
        }


//die();
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        try {
          //  ob_end_clean();
          // $rrrr= $this->phpSpreadsheet->createStreamedResponse($spreadsheet2, 'Xlsx');
//               $dddd= $spreadsheet2->createSheet(1);
//
//
//            $spreadsheet->addSheet($dddd);
            $response = $this->phpSpreadsheet->createStreamedResponse($spreadsheet, 'Xlsx');

            $dispositionHeader = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                (isset($data->title) ? $data->title : 'export').'.xlsx'
            );

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Cache-Control', 'maxage=1');
            $response->headers->set('Content-Disposition', $dispositionHeader);


        } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            var_dump('Error loading file: '.$e->getMessage());die;
        }


        return $response;
        exit();
    }

    public function generateXlsOrientations($annee, $data)
    {
        $spreadsheet = $this->phpSpreadsheet->createSpreadsheet();
    
        $sheet = $spreadsheet->getActiveSheet();
    
        $styles = [
            'h1' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 14
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '8db4e2']
                ]
            ],
            'h2_global' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '4f81bd']
                ]
            ],
            'h2_site' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'fabf8f']
                ]
            ],
            'em_desc' => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 10
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ]
            ],
            'em_desc_jaune' => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 10
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'ffff00']
                ]
            ],
            'em_desc_bold' => [
                'font' => [
                    'bold' => true,
                    'italic' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 10
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ]
            ],
            'em_desc_violet' => [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '7030a0'],
                    'size' => 10
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_BOTTOM,
                ]
            ],
            
            'th_global_fonce' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '4f81bd']
                ]
            ],
            'th_global_clair' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'dce6f1']
                ]
            ],
            'th_site_fonce' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'fabf8f']
                ]
            ],
            'th_site_clair' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'fcd5b4']
                ]
            ],
            'th' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'ffffff']
                ]
            ],
            'th_vert' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'c4d79b']
                ]
            ],
            'th_vert_clair' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'ebf1de']
                ]
            ],
            'th_gauche' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],
            'th_gauche_vert' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'c4d79b']
                ]
            ],
            
            'cell' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ],
            'cell_global_clair' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'dce6f1']
                ]
            ],
            'cell_site_clair' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'fcd5b4']
                ]
            ],
            'cell_vert_clair' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'ebf1de']
                ]
            ],
            'cell_vert_fonce' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'c4d79b']
                ]
            ],
            'cell_vert_fonce_gauche' => [
                'font' => [
                    'color' => ['rgb' => '000000'],
                    'size' => 11
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'c4d79b']
                ]
            ],
        ];
        
        $mois = [];
        for($m = 1; $m <= 12; $m++) {
            $mois[] = new \DateTime($annee.'-'.sprintf('%02d', $m).'-01');
        }
        
        $nbColsTotal = count($data['prescripteurs'])*2 + 4;
        $colFinale = Coordinate::stringFromColumnIndex($nbColsTotal);
    
        $sheet->setCellValue('A1', 'TABLEAU DE BORD DES ORIENTATIONS UFPM PLATEFORME N°' . (string)$data['territoire']->getLot());
        $sheet->mergeCells('A1:'.$colFinale.'1');
        $sheet->getStyle('A1:'.$colFinale.'1')->applyFromArray($styles['h1']);
        $sheet->getRowDimension('1')->setRowHeight(19.50);
        
        for($i = 1; $i < $nbColsTotal; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(12);
        }
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($nbColsTotal))->setWidth(45);
        
        // Date d'actualisation
        $sheet->setCellValue($colFinale.'2', 'actualisé le '.date('d/m/Y'));
    
        $sheet->setCellValue('A3', 'Tableau à actualiser et à transmettre par mail à : joelle.delaforet@regionbourgognefranchecomte.fr et pascale.dumont@regionbourgognefranchecomte.fr tous les 10 du mois
Mettre en copie le chargé d\'animation territorial du Conseil Régional de votre territoire.');
        $sheet->getRowDimension('3')->setRowHeight(27);
        $sheet->getStyle('A3')->applyFromArray($styles['em_desc']);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->mergeCells('A3:'.$colFinale.'3');
        $sheet->setCellValue('A4', 'Pour les plateformes ayant plusieurs localisations remplir les tableaux ci-dessous (fond orange) par sites de réalisation, le tableau de cumul (fond bleu) se remplira automatiquement');
        $sheet->getStyle('A4:'.Coordinate::stringFromColumnIndex($nbColsTotal-1).'4')->applyFromArray($styles['em_desc_jaune']);
        $sheet->setCellValue('A5', 'La case observations - points de vigilance doit permettre de nous signaler rapidement des alertes');
        $sheet->getStyle('A5')->applyFromArray($styles['em_desc_bold']);
        $sheet->setCellValue('A6', '* préciser les organismes orienteurs');
        $sheet->getStyle('A6')->applyFromArray($styles['em_desc_violet']);
    
        $sheet->setCellValue('A8', 'Ensemble des orientations et entrées sur UFPM');
        $sheet->getStyle('A8:'.$colFinale.'8')->applyFromArray($styles['h2_global']);
        
        // -- Tableau global
        $sheet->setCellValue('A10', 'Plateforme N°'.$data['territoire']->getLot());
        $sheet->getStyle('A10')->applyFromArray($styles['th_global_fonce']);
        $sheet->getStyle('A10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getRowDimension('10')->setRowHeight(43.50);
        $sheet->setCellValue('A11', 'TOTAL');
        $sheet->getStyle('A11')->applyFromArray($styles['th_global_fonce']);
        $sheet->getStyle('A11')->getBorders()->getTop()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getRowDimension('11')->setRowHeight(30.75);
        
        $i = 2;
        // Prescripteurs
        foreach($data['prescripteurs'] as $presc) {
            $col = Coordinate::stringFromColumnIndex($i);
            $col2 = Coordinate::stringFromColumnIndex($i+1);
            
            $sheet->setCellValue($col.'10', $presc);
            $sheet->getStyle($col.'10:'.$col2.'10')->applyFromArray($styles['th']);
            $sheet->mergeCells($col.'10:'.$col2.'10');
            $sheet->setCellValue($col.'11', 'Orientations');
            $sheet->getStyle($col.'11')->applyFromArray($styles['th']);
            $sheet->getStyle($col.'11')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->setCellValue($col2.'11', 'Parcours engagés');
            $sheet->getStyle($col2.'11')->applyFromArray($styles['th_global_clair']);
            $sheet->getStyle($col2.'11')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $i+=2;
        }
        
        // Total
        $col = Coordinate::stringFromColumnIndex($i);
        $col2 = Coordinate::stringFromColumnIndex($i+1);
    
        $sheet->setCellValue($col.'10', 'TOTAL');
        $sheet->getStyle($col.'10:'.$col2.'10')->applyFromArray($styles['th_vert']);
        $sheet->mergeCells($col.'10:'.$col2.'10');
        $sheet->setCellValue($col.'11', 'Orientations');
        $sheet->getStyle($col.'11')->applyFromArray($styles['th_vert_clair']);
        $sheet->setCellValue($col2.'11', 'Parcours engagés');
        $sheet->getStyle($col2.'11')->applyFromArray($styles['th_vert']);
        
        $i+=2;
        $col = Coordinate::stringFromColumnIndex($i);
    
        // Orientations
        $sheet->setCellValue($col.'10', 'Observations'.PHP_EOL.'Points de vigilance');
        $sheet->getStyle($col.'10:'.$col.'11')->applyFromArray($styles['th']);
        $sheet->mergeCells($col.'10:'.$col.'11');
        
        $row = 12;
        $nbBassins = count($data['bassins']);
        $nbPresc = count($data['prescripteurs']);
        
        $rowStart = $row;
        foreach($mois as $iM => $m) {
            $i = 1;
            $col = Coordinate::stringFromColumnIndex($i);
            
            $sheet->setCellValue($col.$row, Date::dateTimeToExcel($m));
            $sheet->getStyle($col.$row)->applyFromArray($styles['th_gauche']);
            $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('mmm-yy');
            
            if ($iM < 11) {
                $sheet->getStyle('A'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            }
            if($iM > 0 && $iM < 12) {
                $sheet->getStyle('A'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            }
    
            $i++;
            foreach($data['prescripteurs'] as $presc) {
                $col = Coordinate::stringFromColumnIndex($i);
                $col2 = Coordinate::stringFromColumnIndex($i+1);
    
                $aForm1 = [];
                $aForm2 = [];
                for($iB = 1; $iB <= $nbBassins; $iB++) {
                    $aForm1[] = $col . ($row + ($iB * 20));
                    $aForm2[] = $col2 . ($row + ($iB * 20));
                }
                
                $sheet->setCellValue($col.$row, '='.implode('+', $aForm1));
                $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
                $sheet->setCellValue($col2.$row, '='.implode('+', $aForm2));
                $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_global_clair']);
                $i+=2;
            }
    
            // Colonnes TOTAL
            $aForm1 = [];
            $aForm2 = [];
            for($iP = 2; $iP <= $nbPresc * 2 + 1; $iP+=2) {
                $col = Coordinate::stringFromColumnIndex($iP);
                $col2 = Coordinate::stringFromColumnIndex($iP+1);
                
                $aForm1[] = $col . $row;
                $aForm2[] = $col2 . $row;
            }
    
            $col = Coordinate::stringFromColumnIndex($i);
            $col2 = Coordinate::stringFromColumnIndex($i+1);
    
            $sheet->setCellValue($col.$row, '='.implode('+', $aForm1));
            $sheet->getStyle($col.$row)->applyFromArray($styles['cell_vert_clair']);
            $sheet->setCellValue($col2.$row, '='.implode('+', $aForm2));
            $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_vert_fonce']);
            
            $i+=2;
            
            $col = Coordinate::stringFromColumnIndex($i);
    
            $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
            
            $row++;
        }
    
        $sheet->getStyle('A'.($row-1).':'.$col.($row-1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('A'.$row.':'.$col.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        
        // Total
        $i = 1;
        $col = Coordinate::stringFromColumnIndex($i);
    
        $sheet->setCellValue($col.$row, 'TOTAL');
        $colTotalOrient = Coordinate::stringFromColumnIndex($nbPresc * 2 + 2);
        $colTotalParcoursEngages = Coordinate::stringFromColumnIndex($nbPresc * 2 + 3);
        for($iC = 2; $iC <= $nbPresc * 2 + 3; $iC++) {
            $subCol = Coordinate::stringFromColumnIndex($iC);
            $sheet->setCellValue($subCol.$row, '=SUM('.$subCol.'12:'.$subCol.'23)');
            if($iC <= $nbPresc * 2 + 1) {
                $sheet->setCellValue($subCol . ($row + 1), '=' . $subCol . '24*100/' . $colTotalOrient . '24');
            }
        }
        $sheet->setCellValue($col.($row+1), '%/ total');
        $sheet->getStyle($col.$row)->applyFromArray($styles['th_gauche_vert']);
        $sheet->getStyle($col.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($col.($row+1))->applyFromArray($styles['th_gauche_vert']);
        $sheet->getStyle($col.($row+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
        $i++;
        foreach($data['prescripteurs'] as $presc) {
            $col = Coordinate::stringFromColumnIndex($i);
            $col2 = Coordinate::stringFromColumnIndex($i+1);
        
            $sheet->getStyle($col.$row.':'.$col2.($row+1))->applyFromArray($styles['cell_vert_fonce']);
            $sheet->getStyle($col.($row+1).':'.$col2.($row+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($col.($row+1).':'.$col2.($row+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
            $i+=2;
        }
    
        $col = Coordinate::stringFromColumnIndex($i);
        $col2 = Coordinate::stringFromColumnIndex($i+1);
    
        $sheet->getStyle($col.$row)->applyFromArray($styles['cell_vert_clair']);
        $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_vert_fonce']);
        
        $sheet->getStyle('B'.$row.':'.$col2.$row)->getFont()->setBold(true);
        $sheet->getStyle('B'.($row+1).':'.Coordinate::stringFromColumnIndex($i-1).($row+1))->getFont()->setBold(true);
    
        $i+=2;
    
        $col = Coordinate::stringFromColumnIndex($i);
    
        $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
    
        $sheet->getStyle($colFinale.$rowStart.':'.$colFinale.$row)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle(Coordinate::stringFromColumnIndex($i-2).$row.':'.$colFinale.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
    
        $row+=2;
    
        $sheet->setCellValue('A'.$row, 'Taux d\'entrée UFPM/ nb d\'orientations');
        $sheet->setCellValue('E'.$row, '='.$colTotalParcoursEngages.($row-2).'*100/'.$colTotalOrient.($row-2));
        $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($styles['cell_vert_fonce_gauche']);
        $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('E'.$row)->getFont()->setBold(true);
        // -- Fin Tableau global
    
        $row+=2;
        
        // -- Tableaux sites
        foreach($data['bassins'] as $bassin) {
            $sheet->setCellValue('A'.$row, 'SITE DE '.$bassin->getNom());
            $sheet->getStyle('A'.$row.':'.$colFinale.$row)->applyFromArray($styles['h2_site']);
    
            $row+=2;
            
            $sheet->setCellValue('A'.$row, 'Plateforme N°'.$data['territoire']->getLot());
            $sheet->getStyle('A'.$row)->applyFromArray($styles['th_site_fonce']);
            $sheet->getStyle('A'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getRowDimension($row)->setRowHeight(43.50);
            $sheet->setCellValue('A'.($row+1), (string)$bassin);
            $sheet->getStyle('A'.($row+1))->applyFromArray($styles['th_site_fonce']);
            $sheet->getStyle('A'.($row+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getRowDimension($row+1)->setRowHeight(30.75);
    
            $i = 2;
            // Prescripteurs
            foreach($data['prescripteurs'] as $presc) {
                $col = Coordinate::stringFromColumnIndex($i);
                $col2 = Coordinate::stringFromColumnIndex($i+1);
        
                $sheet->setCellValue($col.$row, $presc);
                $sheet->getStyle($col.$row.':'.$col2.$row)->applyFromArray($styles['th']);
                $sheet->mergeCells($col.$row.':'.$col2.$row);
                $sheet->setCellValue($col.($row+1), 'Orientations');
                $sheet->getStyle($col.($row+1))->applyFromArray($styles['th']);
                $sheet->getStyle($col.($row+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $sheet->setCellValue($col2.($row+1), 'Parcours engagés');
                $sheet->getStyle($col2.($row+1))->applyFromArray($styles['th_site_clair']);
                $sheet->getStyle($col2.($row+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $i+=2;
            }
    
            // Total
            $col = Coordinate::stringFromColumnIndex($i);
            $col2 = Coordinate::stringFromColumnIndex($i+1);
    
            $sheet->setCellValue($col.$row, 'TOTAL');
            $sheet->getStyle($col.$row.':'.$col2.$row)->applyFromArray($styles['th_vert']);
            $sheet->mergeCells($col.$row.':'.$col2.$row);
            $sheet->setCellValue($col.($row+1), 'Orientations');
            $sheet->getStyle($col.($row+1))->applyFromArray($styles['th_vert_clair']);
            $sheet->setCellValue($col2.($row+1), 'Parcours engagés');
            $sheet->getStyle($col2.($row+1))->applyFromArray($styles['th_vert']);
    
            $i+=2;
            $col = Coordinate::stringFromColumnIndex($i);
    
            // Orientations
            $sheet->setCellValue($col.$row, 'Observations'.PHP_EOL.'Points de vigilance');
            $sheet->getStyle($col.$row.':'.$col.($row+1))->applyFromArray($styles['th']);
            $sheet->mergeCells($col.$row.':'.$col.($row+1));
    
            $row += 2;
            $rowStart = $row;
            
            foreach($mois as $iM => $m) {
                $i = 1;
                $col = Coordinate::stringFromColumnIndex($i);
        
                $sheet->setCellValue($col.$row, Date::dateTimeToExcel($m));
                $sheet->getStyle($col.$row)->applyFromArray($styles['th_gauche']);
                $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('mmm-yy');
                
                if ($iM < 11) {
                    $sheet->getStyle('A'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                }
                if($iM > 0 && $iM < 12) {
                    $sheet->getStyle('A'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                }
        
                $i++;
                foreach($data['prescripteurs'] as $kPresc => $presc) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $col2 = Coordinate::stringFromColumnIndex($i+1);
                    
                    $nbOrientations = $data['suivi'][$bassin->getId()][$kPresc]['prescriptions'][$iM + 1] ?? 0;
                    $nbEntrees = $data['suivi'][$bassin->getId()][$kPresc]['entrees'][$iM + 1] ?? 0;
            
                    $sheet->setCellValue($col.$row, $nbOrientations);
                    $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
                    $sheet->setCellValue($col2.$row, $nbEntrees);
                    $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_site_clair']);
                    $i+=2;
                }
    
                // Colonnes TOTAL
                $aForm1 = [];
                $aForm2 = [];
                for($iP = 2; $iP <= $nbPresc * 2 + 1; $iP+=2) {
                    $col = Coordinate::stringFromColumnIndex($iP);
                    $col2 = Coordinate::stringFromColumnIndex($iP+1);
        
                    $aForm1[] = $col . $row;
                    $aForm2[] = $col2 . $row;
                }
    
                $col = Coordinate::stringFromColumnIndex($i);
                $col2 = Coordinate::stringFromColumnIndex($i+1);
    
                $sheet->setCellValue($col.$row, '='.implode('+', $aForm1));
                $sheet->getStyle($col.$row)->applyFromArray($styles['cell_vert_clair']);
                $sheet->setCellValue($col2.$row, '='.implode('+', $aForm2));
                $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_vert_fonce']);
        
                $i+=2;
        
                $col = Coordinate::stringFromColumnIndex($i);
        
                $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
        
                $row++;
            }
    
            $sheet->getStyle('A'.($row-1).':'.$col.($row-1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet->getStyle('A'.$row.':'.$col.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
    
            // Total
            $i = 1;
            $col = Coordinate::stringFromColumnIndex($i);
    
            $sheet->setCellValue($col.$row, 'TOTAL');
            $colTotalOrient = Coordinate::stringFromColumnIndex($nbPresc * 2 + 2);
            $colTotalParcoursEngages = Coordinate::stringFromColumnIndex($nbPresc * 2 + 3);
            for($iC = 2; $iC <= $nbPresc * 2 + 3; $iC++) {
                $subCol = Coordinate::stringFromColumnIndex($iC);
                $sheet->setCellValue($subCol.$row, '=SUM('.$subCol.$rowStart.':'.$subCol.($row-1).')');
                if($iC <= $nbPresc * 2 + 1) {
                    $sheet->setCellValue($subCol . ($row + 1), '=' . $subCol . $row . '*100/' . $colTotalOrient . $row);
                }
            }
            $sheet->setCellValue($col.($row+1), '%/ total');
            $sheet->getStyle($col.$row)->applyFromArray($styles['th_gauche_vert']);
            $sheet->getStyle($col.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($col.($row+1))->applyFromArray($styles['th_gauche_vert']);
            $sheet->getStyle($col.($row+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
            $i++;
            foreach($data['prescripteurs'] as $presc) {
                $col = Coordinate::stringFromColumnIndex($i);
                $col2 = Coordinate::stringFromColumnIndex($i+1);
        
                $sheet->getStyle($col.$row.':'.$col2.($row+1))->applyFromArray($styles['cell_vert_fonce']);
                $sheet->getStyle($col.($row+1).':'.$col2.($row+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($col.($row+1).':'.$col2.($row+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $i+=2;
            }
    
            $col = Coordinate::stringFromColumnIndex($i);
            $col2 = Coordinate::stringFromColumnIndex($i+1);
    
            $sheet->getStyle($col.$row)->applyFromArray($styles['cell_vert_clair']);
            $sheet->getStyle($col2.$row)->applyFromArray($styles['cell_vert_fonce']);
            
            $sheet->getStyle('B'.$row.':'.$col2.$row)->getFont()->setBold(true);
            $sheet->getStyle('B'.($row+1).':'.Coordinate::stringFromColumnIndex($i-1).($row+1))->getFont()->setBold(true);
    
            $i+=2;
    
            $col = Coordinate::stringFromColumnIndex($i);
    
            $sheet->getStyle($col.$row)->applyFromArray($styles['cell']);
    
            $sheet->getStyle($colFinale.$rowStart.':'.$colFinale.$row)->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet->getStyle(Coordinate::stringFromColumnIndex($i-2).$row.':'.$colFinale.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            
            $row+=2;
    
            $sheet->setCellValue('A'.$row, 'Taux d\'entrée UFPM/ nb d\'orientations');
            $sheet->setCellValue('E'.$row, '='.$colTotalParcoursEngages.($row-2).'*100/'.$colTotalOrient.($row-2));
            $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($styles['cell_vert_fonce_gauche']);
            $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            $sheet->getStyle('E'.$row)->getFont()->setBold(true);
            
            $row += 2;
        }
        // -- Fin Tableaux sites
    
        $nom_export = $this->utils->sanitizeFilename(mb_strtolower($data['territoire']->getNom(true), 'UTF-8'));
    
        $sheet->setSelectedCell('A1');
    
        $response = $this->phpSpreadsheet->createStreamedResponse($spreadsheet, 'Xlsx');
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'orientations_stagiaires_ufpm_'.$nom_export.'_'.$annee.'.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
    
        return $response;
    }

    public function generateFiles($dataForProjects=[],$allData=[],$allJourOut=[],$allNameOfAgance=[],$moi=null,$annee=null){
        setlocale(LC_TIME, "fr_FR");
        $dateObj   = DateTime::createFromFormat('!m', $moi);
        $monthName= strftime("%B", strtotime( $dateObj->format('F') ));
        $monthName='   '.substr($monthName,0,3).'-'.substr($annee,-2);

        $headers = [
            ["text" => "PRESTATION", "colspan" => 5, "rowspan" => 2, "row" => 0],
            ["text" => "ACTIV'PROJET-AP2", "colspan" => 13, "rowspan" => 2, "row" => 0]

        ];
        $headers2 = [
            ["text" => "DATE", "colspan" => 2, "rowspan" => 1, "row" => 0],
            ["text" => "PLAGES HORAIRES", "colspan" => 41, "rowspan" => 1, "row" => 0],
            ["text" => "NB PLAGES", "colspan" => 1, "rowspan" => 1, "row" => 0],
        ];

        try {

            $spreadsheetFirst= $this->generateFileXls2(
                [
                    "headers" => $headers,
                    "content" => $dataForProjects[0],
                    "title" => "gg"
                ]
                ,[
                "headers" => $headers2,
                "content" => $allData[0],
                "title" => "ahmad"
            ],$allJourOut[0],$allNameOfAgance[0],$monthName);

            $allFiles=[];

            for ($i=1;$i<count($allData);$i++){


                if($allData[$i][count($allData[$i])-1][count($allData[$i][count($allData[$i])-1])-1]==0){
                    $newSheet=  $spreadsheetFirst->createSheet($i);
                    $newSheet->setTitle($allNameOfAgance[$i]);
                    $newSheet->setCellValue('A1','PAS DE PLAGES COMMANDEES');
                }
                else{
                    $newSheet=clone  $spreadsheetFirst->getSheet(0);
                    $newSheet->fromArray($dataForProjects[$i],null,'A3',true);
                    $newSheet->fromArray($allData[$i],null,'B15',true);
                    $newSheet->setTitle($allNameOfAgance[$i]);
                    $spreadsheetFirst->addSheet($newSheet);
                }


            }
            $spreadsheetFirst->setActiveSheetIndex(0);



           // ob_end_clean();
            $response = $this->phpSpreadsheet->createStreamedResponse($spreadsheetFirst, 'Xlsx',200);




            $dispositionHeader = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'ah'.'.xlsx'
            );

            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Cache-Control', 'maxage=1');
            $response->headers->set('Content-Disposition', $dispositionHeader);

            unset($spreadsheetFirst);

        } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            var_dump('Error loading file: '.$e->getMessage());die;
        }

       // dump($allData);die;


        return $response;
        exit;
        die();


    }

    public function generateFileXls2($data,$data2,$jourOut,$nameOfAgance,$monthName=null) {
        if(is_array($data)) {
            $data = json_decode(json_encode($data));
        }
        if(is_array($data2)) {
            $data2 = json_decode(json_encode($data2));
        }
        $spreadsheet = $this->phpSpreadsheet->createSpreadsheet();
     //   $spreadsheet2 = $this->phpSpreadsheet->createSpreadsheet();


        $sheet = $spreadsheet->getActiveSheet();



       // dump($data);die;


        $header_style = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'alignmentRight' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'alignmentLeft' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFFF00']
            ],

            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $header_style2 = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0000000']
            ],
            'fontSemaine' => [
                'bold' => true,
                'color' => ['rgb' => '548235']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'BDD7EE']
            ],
            'fill2' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'a5a4a4']
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],

                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'borders2' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];


        $content_style = [];
        if(isset($data->content_style)) {
            $content_style = json_decode(json_encode($data->content_style), true);
        }

        // En-tete du tableau
        $row = 1;
        $lettre = 'A';


        $rowspanLetter = null;
        $sheet->mergeCells('A1:E2');
        $sheet->mergeCells('F1:R2');


        for ($y =1 ; $y <13; $y++) {
            $teteP='A';
            for ($x = 0; $x <18; $x++) {
                $sheet->getStyle($teteP.$y)->getBorders()->applyFromArray($header_style2['borders2']);
                $teteP++;
            }

        }

        $red = new Color(Color::COLOR_RED);
        $blue = new Color(Color::COLOR_BLUE);

        foreach($data->headers as $header) {

            if($header->row > $row-1) {
                $row++;

                $lettre = (empty($rowspanLetter) ? 'A' : $rowspanLetter);
                $rowspanLetter = null;
            }

            $sheet->setCellValue($lettre.$row, $header->text);
            $sheet->getStyle($lettre.$row)->applyFromArray($header_style);


            if(!empty($header->rowspan) && $header->rowspan > 1) {
                if(!$this->checkMergedCell($sheet,$sheet->getCell($lettre.$row))){
                    $sheet->mergeCells($lettre.$row.':'.$lettre.($row+($header->rowspan-1)));
                }



                $rowspanLetter = $lettre;
                $rowspanLetter++;

            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter = $lettre;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter++;
                }
                if(!$this->checkMergedCell($sheet,$sheet->getCell($lettre.$row))){
                    $sheet->mergeCells($lettre.$row.':'.$nextLetter.$row);
                }




                $lettre = $nextLetter;
                $lettre++;
            } else {
                $lettre++;
            }




        }


        $row++;

        //added 25/06
        $row++;

        foreach($data->content as $content) {
            $lettre = 'A';
            $lettertest='E';
            $lettertest2='F';
            $lettertest3='R';

            foreach($content as $iHead => $c) {

                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre) - ord('A');


                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre . $row)->getFont()->setColor($red);

                        $c = trim(str_replace("[color=red]", "", $c));


                    }

                }


                $formatted = false;



                if(!$formatted) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style[$i]['alignment'])) {
                    $content_style[$i]['alignment'] = [];
                }

                if(!isset($content_style[$i]['alignment']['horizontal']) || $content_style[$i]['alignment']['horizontal'] == 'left') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style[$i]['alignment']['horizontal']) && $content_style[$i]['alignment']['horizontal'] == 'center') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }



                if(!$this->checkMergedCell($sheet,$sheet->getCell($lettre.$row))){
                    $sheet->mergeCells($lettre.$row.':'.$lettertest.$row);
                }
                if(!$this->checkMergedCell($sheet,$sheet->getCell($lettertest2.$row))){
                    $sheet->mergeCells($lettertest2.$row.':'.$lettertest3.$row);
                }


                $sheet->setCellValue($lettre.$row, $c);


                if(isset($content_style[$i])) {
                    $sheet->getStyle($lettre . $row)->applyFromArray($content_style[$i]);

                }



                $sheet->getColumnDimension($lettre)->setAutoSize(true);


                $sheet->getStyle($lettre . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle($lettre . $row)->getFont()->setSize(10);

                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;

                $lettertest++;
                $lettertest2++;
                $lettertest3++;
            }


            $row++;


        }
        $sheet->getRowDimension('3')->setRowHeight(30);
        $sheet->getRowDimension('8')->setRowHeight(30);
        $sheet->getRowDimension('9')->setRowHeight(30);
        $sheet->getRowDimension('10')->setRowHeight(30);

        $sheet->getStyle('A10')->getFont()->setColor($red)->setSize(12);
        $sheet->getStyle('A11')->getFont()->setColor($red)->setSize(12);
        $sheet->getStyle('A12')->getFont()->setColor($red)->setSize(12);
        $sheet->getStyle('F9')->getFont()->setColor($red);
        $sheet->getStyle('F10')->getFont()->setColor($red);
        $sheet->getStyle('F11')->getFont()->setColor($red);
        $sheet->getStyle('F12')->getFont()->setColor($red);

        $sheet->getStyle('F3')->getFont()->setColor($blue);
        $sheet->getStyle('F4')->getFont()->setColor($blue);
        $sheet->getStyle('F5')->getFont()->setColor($blue);
        $sheet->getStyle('F6')->getFont()->setColor($blue);
        $sheet->getStyle('F7')->getFont()->setColor($blue);
        $sheet->getStyle('F8')->getFont()->setColor($blue);

        $sheet->getStyle('A10')->getAlignment()->applyFromArray($header_style['alignmentRight']);
        $sheet->getStyle('A11')->getAlignment()->applyFromArray($header_style['alignmentRight']);
        $sheet->getStyle('A12')->getAlignment()->applyFromArray($header_style['alignmentRight']);


        $sheet->setCellValue('U6','Mois Concerné par la Planification : '.$monthName);
        $sheet->mergeCells('U6:AD6');
        $sheet->getStyle('U6:Z6')->getFont()->setColor($red);
        $sheet->getStyle('U6:Z6')->getAlignment()->applyFromArray($header_style['alignmentLeft']);
        $sheet->setCellValue('S10','Utiliser 1 tableau de RV par agence et préciser à quelle agence sont destinés les RV de cette page');
        $sheet->mergeCells('S10:AL10');
        $sheet->getStyle('S10:AL10')->getAlignment()->applyFromArray($header_style['alignmentLeft']);
        $sheet->getStyle('S10:AL10')->getFont()->setColor($blue);

        for ($align=3;$align<13;$align++){
            $sheet->getStyle('F'.$align)->getAlignment()->applyFromArray($header_style['alignment']);
        }



        // die();::::::::
        $row++;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $content_style2 = [];
        if(isset($data2->content_style)) {
            $content_style2 = json_decode(json_encode($data2->content_style), true);
        }

        // En-tete du tableau
        //added 26/6//////////////////////////////////////////////////////////////////////////////////////////////
        $tete='A';
        for ($x = 0; $x <44; $x++) {
            $sheet->getStyle($tete.'14')->getBorders()->applyFromArray($header_style2['borders2']);
            $tete++;

        }






        $lettre2 = 'A';
        $rowspanLetter2 = null;
        foreach($data2->headers as $header) {

            if($header->row > $row-1) {
                $row++;
                $lettre2 = (empty($rowspanLetter2) ? 'A' : $rowspanLetter2);
                $rowspanLetter2 = null;
            }

            $sheet->setCellValue($lettre2.$row, $header->text);
            $sheet->getStyle($lettre2.$row)->applyFromArray($header_style2);

            if(!empty($header->rowspan) && $header->rowspan > 1) {
                $sheet->mergeCells($lettre2.$row.':'.$lettre2.($row+($header->rowspan-1)));

                $rowspanLetter2 = $lettre2;
                $rowspanLetter2++;
            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter2 = $lettre2;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter2++;
                }

                $sheet->mergeCells($lettre2.$row.':'.$nextLetter2.$row);

                $lettre2 = $nextLetter2;
                $lettre2++;
            } else {
                $lettre2++;
            }


        }

        $utils = $this->utils;

        $row++;
        $iterator=0;

        foreach($data2->content as $content) {


            $lettre2 = 'A';

            foreach($content as $iHead => $c) {


                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre2) - ord('A');

                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor($red);
                        $c = trim(str_replace("[color=red]", "", $c));
                    }
                }


                $formatted2 = false;
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(8);

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



                if(!$formatted2) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style2[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style2[$i]['alignment'])) {
                    $content_style2[$i]['alignment'] = [];
                }

                if(!isset($content_style2[$i]['alignment']['horizontal']) || $content_style2[$i]['alignment']['horizontal'] == 'left') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style2[$i]['alignment']['horizontal']) && $content_style2[$i]['alignment']['horizontal'] == 'center') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }
                ///////////////////////////////////////////////////HORIZONTAL_CENTER//////////////////////////////////////////////////////////////////////////////////////////////
                $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



                // for hour
                if(strpos($c,'h')!==false ){


                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(8);

                }

                //total row
                if($iHead==42){

                    //  $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(12);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                }
                // total col
                if($iterator==30){

                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                }


                // border for all
                $sheet->getStyle($lettre2 . $row)->getBorders()->applyFromArray($header_style2['borders2']);
                $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // $sheet->mergeCells($lettre2.$row.':'.$lettertestH.$row);

                //added 26/6
                if($iHead==0){

                    $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    // for semaine
                    if(strpos($c,'S')!==false ){
                        $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['fontSemaine']);

                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor( new Color(Color::COLOR_DARKGREEN));
                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);
                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);

                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);


                    }
                    if(is_float($c)){

                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                        $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);

                    }
                    if(isset($content_style2[$i])) {
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                    }
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                    // add border to B letter
                    $sheet->getStyle('B' . $row)->getBorders()->applyFromArray($header_style2['borders2']);


                    if(in_array($content[0],$jourOut) ){
                        // var_dump($c);
                        $sheet->setCellValue($lettre2.$row, '');
                    }
                    $sheet->setCellValue($lettre2.$row, $c);
                    $lettre2++;


                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


                $sheet->setCellValue($lettre2.$row, $c);
                // for jour out
                if(in_array($content[0],$jourOut) ){
                    // var_dump($c);
                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                    $sheet->setCellValue($lettre2.$row, '');
                }
                if(isset($content_style2[$i])) {
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                }
                $sheet->getColumnDimension($lettre2)->setAutoSize(true);
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(5);
                $lettre2++;




            }

            $row++;
            $iterator++;

        }

        $sheet->getStyle('A14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('B14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFont()->setSize(14);
        $sheet->getStyle('A14')->getFont()->setSize(14);
        $sheet->getStyle('C14')->getFont()->setSize(14);


        for ($i=14 ;$i<=45;$i++){
            $sheet->getStyle('AR'.$i)->getFill()->applyFromArray($header_style2['fill2']);
        }



        //die();
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        //var_dump($spreadsheet);die;

        $sheet->setTitle($nameOfAgance);


      //  $objWriter->setPreCalculateFormulas(false)
   return $spreadsheet;

    }
    public function generateSheet($data,$data2,$jourOut,$sheet,$nameOfAgance) {
        if(is_array($data)) {
            $data = json_decode(json_encode($data));
        }
        if(is_array($data2)) {
            $data2 = json_decode(json_encode($data2));
        }


        $header_style = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'alignmentRight' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFFF00']
            ],

            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $header_style2 = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0000000']
            ],
            'fontSemaine' => [
                'bold' => true,
                'color' => ['rgb' => '548235']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'BDD7EE']
            ],
            'fill2' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'a5a4a4']
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],

                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'borders2' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];


        $content_style = [];
        if(isset($data->content_style)) {
            $content_style = json_decode(json_encode($data->content_style), true);
        }

        // En-tete du tableau
        $row = 1;
        $lettre = 'A';


        $rowspanLetter = null;
        $sheet->mergeCells('A1:E2');
        $sheet->mergeCells('F1:R2');


        for ($y =1 ; $y <13; $y++) {
            $teteP='A';
            for ($x = 0; $x <18; $x++) {
                $sheet->getStyle($teteP.$y)->getBorders()->applyFromArray($header_style2['borders2']);
                $teteP++;
            }

        }



        foreach($data->headers as $header) {
            if($header->row > $row-1) {
                $row++;

                $lettre = (empty($rowspanLetter) ? 'A' : $rowspanLetter);
                $rowspanLetter = null;
            }

            $sheet->setCellValue($lettre.$row, $header->text);
            $sheet->getStyle($lettre.$row)->applyFromArray($header_style);


            if(!empty($header->rowspan) && $header->rowspan > 1) {
                $sheet->mergeCells($lettre.$row.':'.$lettre.($row+($header->rowspan-1)));


                $rowspanLetter = $lettre;
                $rowspanLetter++;

            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter = $lettre;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter++;
                }

                $sheet->mergeCells($lettre.$row.':'.$nextLetter.$row);



                $lettre = $nextLetter;
                $lettre++;
            } else {
                $lettre++;
            }




        }


        $row++;

        //added 25/06
        $row++;

        foreach($data->content as $content) {
            $lettre = 'A';
            $lettertest='E';
            $lettertest2='F';
            $lettertest3='R';

            foreach($content as $iHead => $c) {

                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre) - ord('A');


                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre . $row)->getFont()->setColor($red);

                        $c = trim(str_replace("[color=red]", "", $c));


                    }

                }


                $formatted = false;



                if(!$formatted) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style[$i]['alignment'])) {
                    $content_style[$i]['alignment'] = [];
                }

                if(!isset($content_style[$i]['alignment']['horizontal']) || $content_style[$i]['alignment']['horizontal'] == 'left') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style[$i]['alignment']['horizontal']) && $content_style[$i]['alignment']['horizontal'] == 'center') {
                    $content_style[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }

                $sheet->mergeCells($lettre.$row.':'.$lettertest.$row);
                $sheet->mergeCells($lettertest2.$row.':'.$lettertest3.$row);
                $sheet->setCellValue($lettre.$row, $c);


                if(isset($content_style[$i])) {
                    $sheet->getStyle($lettre . $row)->applyFromArray($content_style[$i]);

                }
                $red = new Color(Color::COLOR_RED);
                $blue = new Color(Color::COLOR_BLUE);


                $sheet->getColumnDimension($lettre)->setAutoSize(true);


                $sheet->getStyle($lettre . $row)->getAlignment()->setWrapText(true);

                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;
                $lettre++;

                $lettertest++;
                $lettertest2++;
                $lettertest3++;
            }


            $row++;


        }
        $sheet->getRowDimension('3')->setRowHeight(30);
        $sheet->getRowDimension('8')->setRowHeight(30);
        $sheet->getRowDimension('9')->setRowHeight(30);
        $sheet->getRowDimension('10')->setRowHeight(30);

        $sheet->getStyle('A10')->getFont()->setColor($red);
        $sheet->getStyle('A11')->getFont()->setColor($red);
        $sheet->getStyle('A12')->getFont()->setColor($red);
        $sheet->getStyle('F9')->getFont()->setColor($red);
        $sheet->getStyle('F10')->getFont()->setColor($red);
        $sheet->getStyle('F11')->getFont()->setColor($red);
        $sheet->getStyle('F12')->getFont()->setColor($red);

        $sheet->getStyle('F3')->getFont()->setColor($blue);
        $sheet->getStyle('F4')->getFont()->setColor($blue);
        $sheet->getStyle('F5')->getFont()->setColor($blue);
        $sheet->getStyle('F6')->getFont()->setColor($blue);
        $sheet->getStyle('F7')->getFont()->setColor($blue);
        $sheet->getStyle('F8')->getFont()->setColor($blue);

        $sheet->getStyle('A10')->getAlignment()->applyFromArray($header_style['alignmentRight']);
        $sheet->getStyle('A11')->getAlignment()->applyFromArray($header_style['alignmentRight']);
        $sheet->getStyle('A12')->getAlignment()->applyFromArray($header_style['alignmentRight']);

        for ($align=3;$align<13;$align++){
            $sheet->getStyle('F'.$align)->getAlignment()->applyFromArray($header_style['alignment']);
        }



        // die();::::::::
        $row++;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $content_style2 = [];
        if(isset($data2->content_style)) {
            $content_style2 = json_decode(json_encode($data2->content_style), true);
        }

        // En-tete du tableau
        //added 26/6//////////////////////////////////////////////////////////////////////////////////////////////
        $tete='A';
        for ($x = 0; $x <44; $x++) {
            $sheet->getStyle($tete.'14')->getBorders()->applyFromArray($header_style2['borders2']);
            $tete++;

        }






        $lettre2 = 'A';
        $rowspanLetter2 = null;
        foreach($data2->headers as $header) {

            if($header->row > $row-1) {
                $row++;
                $lettre2 = (empty($rowspanLetter2) ? 'A' : $rowspanLetter2);
                $rowspanLetter2 = null;
            }

            $sheet->setCellValue($lettre2.$row, $header->text);
            $sheet->getStyle($lettre2.$row)->applyFromArray($header_style2);

            if(!empty($header->rowspan) && $header->rowspan > 1) {
                $sheet->mergeCells($lettre2.$row.':'.$lettre2.($row+($header->rowspan-1)));

                $rowspanLetter2 = $lettre2;
                $rowspanLetter2++;
            }

            if(!empty($header->colspan) && $header->colspan > 1) {
                $nextLetter2 = $lettre2;
                for($i=0; $i < $header->colspan-1; $i++) {
                    $nextLetter2++;
                }

                $sheet->mergeCells($lettre2.$row.':'.$nextLetter2.$row);

                $lettre2 = $nextLetter2;
                $lettre2++;
            } else {
                $lettre2++;
            }


        }

        $utils = $this->utils;

        $row++;
        $iterator=0;

        foreach($data2->content as $content) {

            $lettre2 = 'A';
          //  $sheet->fromArray($content,null,$lettre2.$row);
            foreach($content as $iHead => $c) {


                if($c === null) {
                    continue;
                }
                $c = trim($c);
                $i = ord($lettre2) - ord('A');

                if(!empty($c)) {
                    if(strpos($c, "[color=red]") !== false) {
                        $red = new Color(Color::COLOR_RED);
                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor($red);
                        $c = trim(str_replace("[color=red]", "", $c));
                    }
                }


                $formatted2 = false;
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(8);

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



                if(!$formatted2) {
                    if (is_numeric($c)) {
                        if (round($c) == $c) {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        } else {
                            $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    } else if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $c)) {
                        $sheet->getStyle($lettre2 . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                        $date = \DateTime::createFromFormat('d/m/Y', $c);
                        $date->setTime(0, 0, 0);
                        $c = Date::PHPToExcel($date);

                        $content_style2[$i]['alignment']['horizontal'] = 'center';
                    }
                }

                if(!isset($content_style2[$i]['alignment'])) {
                    $content_style2[$i]['alignment'] = [];
                }

                if(!isset($content_style2[$i]['alignment']['horizontal']) || $content_style2[$i]['alignment']['horizontal'] == 'left') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
                } else if(isset($content_style2[$i]['alignment']['horizontal']) && $content_style2[$i]['alignment']['horizontal'] == 'center') {
                    $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                }
                ///////////////////////////////////////////////////HORIZONTAL_CENTER//////////////////////////////////////////////////////////////////////////////////////////////
                $content_style2[$i]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



                // for hour
                if(strpos($c,'h')!==false ){


                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(8);

                }

                //total row
                if($iHead==42){

                    //  $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(12);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                }
                // total col
                if($iterator==30){

                    $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                    $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);
                }
                // for jour out
                if(in_array($content[0],$jourOut) ){
                    // var_dump($c);
                    $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                }

                // border for all
                $sheet->getStyle($lettre2 . $row)->getBorders()->applyFromArray($header_style2['borders2']);
                $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // $sheet->mergeCells($lettre2.$row.':'.$lettertestH.$row);

                //added 26/6
                if($iHead==0){

                    $sheet->mergeCells('A'.$row.':'.'B'.$row);
                    // for semaine
                    if(strpos($c,'S')!==false ){
                        $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['fontSemaine']);

                        $sheet->getStyle($lettre2 . $row)->getFont()->setColor( new Color(Color::COLOR_DARKGREEN));
                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);
                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);

                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);


                    }
                    if(is_float($c)){

                        $sheet->getStyle($lettre2 . $row)->getFont()->setSize(14);

                        $sheet->getStyle($lettre2 . $row )->getFill()->applyFromArray($header_style2['fill2']);
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                        $sheet->getStyle($lettre2 . $row)->getFont()->applyFromArray($header_style2['font']);

                    }
                    if(isset($content_style2[$i])) {
                        $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                    }
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($header_style2['alignment']);
                    // add border to B letter
                    $sheet->getStyle('B' . $row)->getBorders()->applyFromArray($header_style2['borders2']);

                    $sheet->setCellValue($lettre2.$row, $c);
                    if(in_array($content[0],$jourOut) ){
                        // var_dump($c);
                        $sheet->setCellValue($lettre2.$row, '');
                    }
                    $lettre2++;


                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


                $sheet->setCellValue($lettre2.$row, $c);

                if(isset($content_style2[$i])) {
                    $sheet->getStyle($lettre2 . $row)->applyFromArray($content_style2[$i]);

                }
                $sheet->getColumnDimension($lettre2)->setAutoSize(true);
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $sheet->getStyle($lettre2)->getFont()->setSize(5);
                $lettre2++;




            }

            $row++;
            $iterator++;

        }

        $sheet->getStyle('A14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('B14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFill()->applyFromArray($header_style2['fill2']);
        $sheet->getStyle('AR14')->getFont()->setSize(14);
        $sheet->getStyle('A14')->getFont()->setSize(14);
        $sheet->getStyle('C14')->getFont()->setSize(14);


        for ($i=14 ;$i<=45;$i++){
            $sheet->getStyle('AR'.$i)->getFill()->applyFromArray($header_style2['fill2']);
        }



        //die();
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        $sheet->setTitle($nameOfAgance);
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       return $sheet;

    }

    public function checkMergedCell($sheet, $cell){
        foreach ($sheet->getMergeCells() as $cells) {
            if ($cell->isInRange($cells)) {
                // Cell is merged!
                return true;
            }
        }
        return false;
    }


}




//        $allFiles=[];
//
//        for($i=1;$i<3;$i++){
//            $spreadsheet=   $this->generateSheet(
//                [
//                    "headers" => $headers,
//                    "content" => $dataForProjects[$i],
//                    "title" => "ahmad"
//                ]
//                ,[
//                "headers" => $headers2,
//                "content" => $allData[$i],
//                "title" => "ahmad"
//            ],$allJourOut[$i]);
//
//            array_push($allFiles,$spreadsheet);
//        }



//   $newSheet=  $spreadsheetFirst->createSheet($i);

//                 $this->generateSheet(
//                  [
//                      "headers" => $headers,
//                      "content" => $dataForProjects[$i],
//                      "title" => "ahmad"
//                  ]
//                  ,[
//                  "headers" => $headers2,
//                  "content" => $allData[$i],
//                  "title" => "ahmad"
//              ],$allJourOut[$i],$newSheet,$allNameOfAgance[$i]);

//                array_push($allFiles,$newSheet);
