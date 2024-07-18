<?php
class GraphBar
{

  static function RenderYear($i)
  {
    if ($i == date('Y', time())) {
      return $i;
    } elseif ($i % 4 == 0 && $i < (date('Y', time()) - 3)) {
      return $i;
    } else {
      return '';
    }
  }

  static function slices($arr)
  {

    $year = 0;
    $infoA = '';
    $infoB = '';
    $infoC = '';
    $infoD = '';
    $output = '';

    foreach ($arr as $years) {
      $year = (int)$years['year'];
      if (isset($years[0])) {
        $infoA = (int)$years[0];
      } else {
        $infoA = 0;
      }
      if (isset($years[1])) {
        $infoB = (int)$years[1];
      } else {
        $infoB = 0;
      }
      if (isset($years[2])) {
        $infoC = (int)$years[2];
      } else {
        $infoC = 0;
      }
      if (isset($years[3])) {
        $infoD = (int)$years[3];
      } else {
        $infoD = 0;
      }
      $output_array[] = "<div class='c-gppg-slice'>";
      if ($infoA != 0) {
        $output_array[] = "<div class='c-gppg-bar' data-type='1' data-weight='$infoA'></div>";
      }
      if ($infoB != 0) {
        $output_array[] = "<div class='c-gppg-bar' data-type='2' data-weight='$infoB'></div>";
      }
      if ($infoC != 0) {
        $output_array[] = "<div class='c-gppg-bar' data-type='3' data-weight='$infoC'></div>";
      }
      if ($infoD != 0) {
        $output_array[] = "<div class='c-gppg-bar' data-type='4' data-weight='$infoD'></div>";
      }
      $output_array[] = "<span class='c-gppg-year'>$year</span></div>";
      $output = implode(' ', $output_array);
    }
    return $output;

    unset($output_array);
    unset($year);
    unset($infoA);
    unset($infoB);
    unset($infoC);
    unset($infoD);
  }

  static function graph($title, $arrData, $arrLegends, $lines)
  {
    //echo "<pre>" . print_r($arrData, true) . "</pre>";
    $renderSlices = GraphBar::slices($arrData);

    for ($i_lines = 1; $i_lines <= $lines; $i_lines++) {
      $renderLines[] =  "<hr class='c-gppg-grid-line' />";
    }
    $renderLines = implode('', $renderLines);

    for ($i_levels = $lines; $i_levels >= 0; $i_levels -= 1) {
      $renderLevels[] =  "<div class='c-gppg-level'>$i_levels</div>";
    }
    $renderLevels = implode('', $renderLevels);

    $renderLegendsArr = [];
    $i_aux = 1;
    foreach ($arrLegends as $legend) {
      $renderLegendsArr[] = '<div class="c-gppg-legend" data-number="' . $i_aux . '">' . $legend . '</div>';
      $i_aux++;
    }
    $renderLegends = implode('', $renderLegendsArr);

    echo ("
      <a class='u-skip' href='#skip-graphbar'>Pular nuvens de palavras</a>
      <div class='c-gppg'>
      <div class='c-gppg-infos'>
        <div class='c-gppg-title t-title'>$title</div>
        <div class='c-gppg-legends'>
          $renderLegends
        </div>
      </div>
      
      <div class='c-gppg-plot'>

        
        <div class='c-gppg-slice-zero'>
          <div class='c-gppg-level'></div>
          $renderLevels
        </div>
        
        $renderSlices
        
        <div class='c-gppg-grid'>
          $renderLines
        </div>

        </div>
      </div>
      <span class='u-skip' id='skip-graphbar'></span>
    ");
  }
}
