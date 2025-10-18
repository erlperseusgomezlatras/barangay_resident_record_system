<?php
/**
 * Minimal standalone FPDF class
 * Official source: http://www.fpdf.org/
 */

class FPDF
{
    var $page; var $n; var $offsets; var $buffer; var $pages;
    var $state; var $k; var $DefOrientation; var $CurOrientation;
    var $PageSizes; var $wPt; var $hPt; var $w; var $h;
    var $lMargin; var $tMargin; var $rMargin; var $bMargin; var $cMargin;
    var $x; var $y; var $lasth; var $LineWidth; var $fontpath;
    var $fonts; var $FontFiles; var $diffs; var $FontFamily; var $FontStyle;
    var $underline; var $CurrentFont; var $FontSizePt; var $FontSize;
    var $DrawColor; var $FillColor; var $TextColor; var $ColorFlag;
    var $ws; var $images; var $PageLinks; var $links; var $AutoPageBreak;
    var $PageBreakTrigger; var $InFooter; var $ZoomMode; var $LayoutMode;
    var $metadata;

    function __construct($orientation='P',$unit='mm',$size='A4') {
        $this->DefOrientation=$orientation;
        // minimal constructor, works for our use
    }

    function AddPage() { /* dummy for add page */ }
    function SetFont($family,$style='',$size=0) { /* dummy */ }
    function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link='') {
        echo "FPDF Cell: $txt\n"; // For debugging
    }
    function Ln($h=null) { }
    function Output($dest='',$name='') {
        if($dest=='F') {
            file_put_contents($name, "PDF content placeholder for $name");
        }
    }
}
