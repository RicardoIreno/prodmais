<!DOCTYPE html>
<html lang="pt-br" dir="ltr">

<head>
  <?php

  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Credentials: true");
  header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
  header('Access-Control-Max-Age: 1000');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

  require 'inc/config.php';
  require 'inc/meta-header.php';
  require 'inc/functions.php';
  require 'inc/components/SList.php';

  if (isset($fields)) {
    $_POST["fields"] = $fields;
  }

  if (empty($_POST)) {
    $_POST['search'] = "";
  }

  $result_post = Requests::postParser($_POST);
  if (!empty($_POST)) {
    $limit_records = $result_post['limit'];
    $page = $result_post['page'];
    $params = [];
    $params["index"] = $index;
    $params["body"] = $result_post['query'];
    $cursorTotal = $client->count($params);
    $total_records = $cursorTotal["count"];
    if (isset($_POST["sort"])) {
      $result_post['query']["sort"][$_POST["sort"]]["unmapped_type"] = "long";
      $result_post['query']["sort"][$_POST["sort"]]["missing"] = "_last";
      $result_post['query']["sort"][$_POST["sort"]]["order"] = "desc";
      $result_post['query']["sort"][$_POST["sort"]]["mode"] = "max";
    } else {
      $result_post['query']['sort']['datePublished.keyword']['order'] = "desc";
      $result_post['query']["sort"]["_uid"]["unmapped_type"] = "long";
      $result_post['query']["sort"]["_uid"]["missing"] = "_last";
      $result_post['query']["sort"]["_uid"]["order"] = "desc";
      $result_post['query']["sort"]["_uid"]["mode"] = "max";
    }
    $params["body"] = $result_post['query'];
    $params["size"] = $limit_records;
    $params["from"] = $result_post['skip'];
    $cursor = $client->search($params);
  } else {
    $limit_records = 50;
    $page = 1;
    $total = 0;
    $cursor["hits"]["hits"] = [];
  }

  /*pagination - start*/
  $get_data = $_POST;
  /*pagination - end*/

  ?>
  <meta charset="utf-8" />
  <title>
    <?php echo $branch; ?> - Resultado da busca
  </title>
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
  <meta name="description" content="Prodmais" />
  <meta name="keywords" content="Produção acadêmica, lattes, ORCID" />

</head>

<body id="app-result" data-theme="<?php echo $theme; ?>">
  <?php
  if (file_exists('inc/google_analytics.php')) {
    include 'inc/google_analytics.php';
  }
  ?>
  <!-- NAV -->
  <?php require 'inc/navbar.php'; ?>
  <!-- /NAV -->

  <div class="p-result-container">

    <nav class="p-result-nav">
      <details id="filterlist" class="c-filterlist" onload="resizeMenu" open="">
        <?php if (!empty($_REQUEST['search'])) : ?>
          <div class="c-term">Termo pesquisado:
            <?php print_r($_REQUEST['search']); ?>
          </div>
        <?php endif ?>
        <?php
        if (isset($_REQUEST['filter'])) {
          $filter_aplicado_array =  $_REQUEST['filter'];
          //var_dump($filter_aplicado_array);
          echo '<div class="c-term">';
          echo '<p>Filtros aplicados:</p>';
          foreach ($_REQUEST['filter'] as $filter) {
            echo '<div class="c-term">';
            echo '<form action="result.php" method="post">';
            echo '<input type="hidden" name="search" value="' . $_REQUEST["search"] . '">';
            $array_sem_filtro = array_diff($filter_aplicado_array, [$filter]);
            foreach ($array_sem_filtro as $filtro_aplicado) {
              echo '<input type="hidden" name="filter[]" value="' . $filtro_aplicado . '">';
            }
            $filter_name = str_replace('tipo:', 'Tipo: ', $filter);
            $filter_name = str_replace('vinculo.ppg_nome:', 'Programa de Pós-Graduação: ', $filter_name);
            $filter_name = str_replace('vinculo.instituicao:', 'Instituição: ', $filter_name);
            $filter_name = str_replace('country:', 'País de publicação: ', $filter_name);
            $filter_name = str_replace('language:', 'Idioma: ', $filter_name);
            $filter_name = str_replace('about:', 'Palavra-chave: ', $filter_name);
            $filter_name = str_replace('datePublished:', 'Ano de publicação: ', $filter_name);

            echo '<input class="c-filterdrop__item-name" style="text-decoration: none; color: initial;" type="submit" value="' . $filter_name . ' (Remover)" />';
            echo '</form>';
            echo '</div>';
          }
          echo '</div>';
          //echo '<div class="c-term">Filtro aplicado: ' . implode('', $filter_array) . '</div>';
        }
        ?>
        <summary class="c-filterlist__header">
          <h3 class="c-filterlist__title">Refinar resultados</h3>
        </summary>

        <div class="c-filterlist__content" id="app">

          <?php
          $facets = new Facets();
          $facets->query = $result_post['query'];

          if (!isset($_POST)) {
            $_POST = null;
          }

          if ($mostrar_instituicao) {
            echo ($facets->facet(1, "vinculo.instituicao", 100, "Instituição", null, "_key", $_POST, "result.php"));
          }

          echo ($facets->facet(3, "vinculo.ppg_nome", 100, "Nome do PPG", "asc", "_key", $_POST, "result.php"));
          if ($mostrar_area_concentracao) {
            echo ($facets->facet(4, "vinculo.area_concentracao", 100, "Área de concentração", null, "_key", $_POST, "result.php"));
          }
          echo ($facets->facet(5, "tipo", 100, "Tipo de material", null, "_key", $_POST, "result.php"));
          echo ($facets->facet_author(basename(__FILE__), "author.person.name", 100, "Nome completo do autor", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(6, "vinculo.nome", 100, "Nome do autor vinculado à instituição", null, "_key", $_POST, "result.php"));
          //echo ($facets->facet(basename(__FILE__), "vinculo.lattes_id", 100, "ID do Lattes", null, "_key", $_POST, "result.php"));

          echo ($facets->facet(7, "country", 200, "País de publicação", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(8, "datePublished", 120, "Ano de publicação", "desc", "_key", $_POST, "result.php"));
          echo ($facets->facet(9, "language", 40, "Idioma", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(2, "vinculo.tipvin", 100, "Tipo de vínculo", null, "_key", $_POST, "result.php"));
          // echo ($facets->facet(basename(__FILE__), "lattes.natureza", 100, "Natureza", null, "_key", $_POST, "result.php"));
          // echo ($facets->facet(basename(__FILE__), "lattes.meioDeDivulgacao", 100, "Meio de divulgação", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(10, "about", 100, "Palavras-chave", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(11, "agencia_de_fomento", 100, "Agências de fomento", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(12, "area_do_conhecimento.nomeGrandeAreaDoConhecimento", 100, "Nome da grande área do conhecimento", null, "_key", $_POST, "result.php"));
          //echo($facets->facet(basename(__FILE__), "area_do_conhecimento.nomeDaAreaDoConhecimento", 100, "Nome da Área do Conhecimento", null, "_key", $_POST, "result.php"));
          //echo($facets->facet(basename(__FILE__), "area_do_conhecimento.nomeDaSubAreaDoConhecimento", 100, "Nome da Sub Área do Conhecimento", null, "_key", $_POST, "result.php"));
          //echo($facets->facet(basename(__FILE__), "area_do_conhecimento.nomeDaEspecialidade", 100, "Nome da Especialidade", null, "_key", $_POST, "result.php"));

          echo ($facets->facet(13, "trabalhoEmEventos.classificacaoDoEvento", 100, "Classificação do evento", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(14, "EducationEvent.name", 100, "Nome do evento", null, "_key", $_POST, "result.php"));
          //echo ($facets->facet("publisher.organization.location", 100, "Cidade", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(15, "trabalhoEmEventos.anoDeRealizacao", 100, "Ano de realização do evento", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(16, "trabalhoEmEventos.tituloDosAnaisOuProceedings", 100, "Título dos anais", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(17, "trabalhoEmEventos.isbn", 100, "ISBN dos anais", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(18, "trabalhoEmEventos.nomeDaEditora", 100, "Editora dos anais", null, "_key", $_POST, "result.php"));
          //echo ($facets->facet(basename(__FILE__), "trabalhoEmEventos.cidadeDaEditora", 100, "Cidade da editora", null, "_key", $_POST, "result.php"));

          echo ($facets->facet(19, "isPartOf.name", 100, "Título do periódico", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(20, "qualis.extrato", 100, "Extrato QUALIS", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(21, "qualis.area", 100, "Área QUALIS", null, "_key", $_POST, "result.php"));

          // echo($facets->facetExistsField(basename(__FILE__), "ExternalData.crossref.message.title", 100, "Dados coletados da Crossref?", null, "_key", $_POST, "result.php"));
          // echo($facets->facet(22, "ExternalData.crossref.message.author.affiliation.name", 100, "Crossref - Afiliação", null, "_key", $_POST, "result.php"));
          // echo($facets->facet(23, "ExternalData.crossref.message.funder.name", 100, "Crossref - Agência de financiamento", null, "_key", $_POST, "result.php"));
          // echo($facets->facet(24, "ExternalData.crossref.message.funder.DOI", 100, "Crossref - Agência de financiamento - DOI", null, "_key", $_POST, "result.php"));
          // echo($facets->facet_range(basename(__FILE__), "ExternalData.crossref.message.is-referenced-by-count", 100, "Crossref - Número de citações obtidas", null, "_key", $_POST, "result.php"));

          echo ($facets->facet(25, "vinculo.campus", 100, "Campus", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(26, "vinculo.desc_gestora", 100, "Gestora", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(27, "vinculo.unidade", 100, "Unidade", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(28, "vinculo.departamento", 100, "Departamento", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(29, "vinculo.divisao", 100, "Divisão", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(30, "vinculo.secao", 100, "Seção", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(31, "vinculo.genero", 100, "Gênero", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(32, "vinculo.desc_nivel", 100, "Nível", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(33, "vinculo.desc_curso", 100, "Curso", null, "_key", $_POST, "result.php"));

          if ($mostrar_existe_doi) {
            echo ($facets->facetExistsField(basename(__FILE__), "doi", 2, "Possui DOI preenchido?", null, "_key", $_POST, "result.php"));
          }

          if ($mostrar_openalex) {
            echo ($facets->facet(34, "openalex.open_access.oa_status", 100, "Status de acesso aberto segundo o OpenAlex?", null, "_key", $_POST, "result.php"));
            echo ($facets->facet(35, "openalex.authorships.institutions.display_name", 100, "Instituição normalizada - OpenAlex", null, "_key", $_POST, "result.php"));
            echo ($facets->facet(36, "openalex_referenced_works.name", 50, "Trabalhos mais citados - OpenAlex", null, "_key", $_POST, "result.php"));
            echo ($facets->facet(37, "openalex_referenced_works.datePublished", 50, "Ano de publicação dos trabalhos mais citados - OpenAlex", "desc", "_key", $_POST, "result.php"));
            echo ($facets->facet(38, "openalex_referenced_works.authorships.author.display_name", 50, "Autores mais citados - OpenAlex", null, "_key", $_POST, "result.php"));
            echo ($facets->facet(39, "openalex_referenced_works.language", 10, "Idioma dos trabalhos mais citados - OpenAlex", null, "_key", $_POST, "result.php"));
            echo ($facets->facet(40, "openalex_referenced_works.source", 50, "Nome da publicação dos trabalhos mais citados - OpenAlex", null, "_key", $_POST, "result.php"));
            //echo ($facets->facetcited(basename(__FILE__), "openalex.referenced_works", 5, "5 trabalhos mais citados - OpenAlex", null, "_key", $_POST, "result.php"));
            echo ($facets->facet_range(basename(__FILE__), "openalex.cited_by_count", 100, "Número de citações obtidas - OpenAlex", null, "_key", $_POST, "result.php"));
          }

          echo ($facets->facet(41, "openalex.sustainable_development_goals.display_name", 100, "Sustainable Development Goals - OpenAlex", null, "_key", $_POST, "result.php"));
          echo ($facets->facet(42, "aurorasdg.most_probable_sdg", 100, "Sustainable Development Goals - Aurora", null, "_key", $_POST, "result.php"));

          ?>

        </div>
      </details>
    </nav>

    <main class="p-result-main">

      <div class="p-result-search-ctn">

        <form class="u-100" action="result.php" method="POST" accept-charset="utf-8"
          enctype="multipart/form-data" id="search">

          <div class="c-searcher">
            <input class="" type="text" name="search"
              placeholder="Pesquise por palavras chave ou nomes de autores"
              aria-label="Pesquise por palavras chave ou nomes de autores"
              aria-describedby="button-addon2" />
            <button class="c-searcher__btn" type="submit" form="search" value="Submit">
              <i class="i i-lupa c-searcher__btn-ico"></i>
            </button>
          </div>
        </form>

      </div>


      <?php ui::newpagination($page, $total_records, $limit_records, $_POST, "result", 'result'); ?>
      <br />

      <?php if ($total_records == 0) : ?>
        <br />
        <div class="alert alert-info" role="alert">
          Sua busca não obteve resultado. Você pode refazer sua busca abaixo:<br /><br />
          <form action="result.php">
            <div class="form-group">
              <input type="text" name="search" class="form-control" id="searchQuery"
                aria-describedby="searchHelp" placeholder="Pesquise por termo ou autor">
              <small id="searchHelp" class="form-text text-muted">Dica: Use * para busca por radical. Ex:
                biblio*.</small>
              <small id="searchHelp" class="form-text text-muted">Dica 2: Para buscas exatas, coloque entre
                ""</small>
              <small id="searchHelp" class="form-text text-muted">Dica 3: Você também pode usar operadores
                booleanos:
                AND, OR</small>
            </div>
            <button type="submit" class="btn btn-primary">Pesquisar</button>

          </form>
        </div>
        <br /><br />
      <?php endif; ?>

      <?php

      foreach ($cursor["hits"]["hits"] as $r) {
        if (isset($r["_source"]["author"])) {
          foreach ($r["_source"]["author"] as $author) {
            $authors[] = $author["person"]["name"];
          }
        } else {
          $authors[] = '';
        }


        !empty($r["_source"]['url']) ? $url = $r["_source"]['url'] : $url = '';
        !empty($r["_source"]['doi']) ? $doi = $r["_source"]['doi'] : $doi = '';
        !empty($r['_source']['isPartOf']['issn']) ? $issn = $r['_source']['isPartOf']['issn'] : $issn = '';
        !empty($r['_source']['isPartOf']['name']) ? $refName = $r['_source']['isPartOf']['name'] : $refName = '';
        !empty($r['_source']['datePublished']) ? $published = $r['_source']['datePublished'] : $published = '';
        isset($r['_source']['openalex']['cited_by_count']) ? $cited_by_count = strval($r['_source']['openalex']['cited_by_count']) : $cited_by_count = '';
        isset($r['_source']['aurorasdg']) ? $aurorasdg = $r['_source']['aurorasdg'] : $aurorasdg = '';
        isset($r['_source']['qualis']['extrato']) ? $qualis = $r['_source']['qualis']['extrato'] : $qualis = '';

        SList::IntelectualProduction(
          $type = $r['_source']['tipo'],
          $name = $r['_source']['name'],
          $authors = $authors,
          $doi,
          $url,
          $issn,
          $refName,
          $refVol = '',
          $refFascicle = '',
          $refPage = '',
          $datePublished = $published,
          $cited_by_count,
          $aurorasdg,
          $qualis
        );
        unset($authors);
      }


      (!empty($datePublished) && !empty($id)) ? $query = DadosInternos::queryProdmais($name, $datePublished, $id) : $query = '';

      ui::newpagination($page, $total_records, $limit_records, $_POST, 'result');
      ?>

    </main>

    <script>
      new Vue({
        el: '#app',
        data: {
          isVisible1: false,
          isVisible2: false,
          isVisible3: false,
          isVisible4: false,
          isVisible5: false,
          isVisible6: false,
          isVisible7: false,
          isVisible8: false,
          isVisible8: false,
          isVisible10: false,
          isVisible11: false,
          isVisible12: false,
          isVisible13: false,
          isVisible14: false,
          isVisible15: false,
          isVisible16: false,
          isVisible17: false,
          isVisible18: false,
          isVisible19: false,
          isVisible20: false,
          isVisible21: false,
          isVisible22: false,
          isVisible23: false,
          isVisible24: false,
          isVisible25: false,
          isVisible26: false,
          isVisible27: false,
          isVisible28: false,
          isVisible29: false,
          isVisible30: false,
          isVisible31: false,
          isVisible32: false,
          isVisible33: false,
          isVisible34: false,
          isVisible35: false,
          isVisible36: false,
          isVisible37: false,
          isVisible38: false,
          isVisible39: false,
          isVisible40: false,
          isVisible41: false,
          isVisible42: false,
          isVisible43: false,
          isVisible44: false,
          isVisible45: false,
          isVisible46: false,
          isVisible47: false,
          isVisible48: false,
        },
        methods: {
          toggleDiv(id) {
            id.toString();
            var str = 'isVisible' + id;
            this[str] = !this[str];
            console.log(this.str);
          }
        }
      });
    </script>

  </div> <!-- end result-container -->

  <?php include('inc/footer.php'); ?>
  <script src="inc/js/pages/result.js"></script>

  <!-- PlumX Script -->
  <script type="text/javascript" src="//cdn.plu.mx/widget-details.js"></script>

  <!-- Aurora Widget -->
  <script type="text/javascript" src="assets/js/widget.js"></script>

</body>

</html>