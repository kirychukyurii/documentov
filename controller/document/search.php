<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ControllerDocumentSearch extends Controller
{

  private $step = 0;

  public function index()
  {
    $this->load->language('document/search');
    $this->document->setTitle($this->language->get('text_fulltextsearch'));
    $footer = $this->load->controller('common/footer');
    //$header = $this->load->controller('common/header');
    //$header_doc = $this->load->view('document/form_header', array());
    //$footer_doc = $this->load->view('document/form_footer', array());
    $search_string = "";
    if (!empty($this->request->get['search'])) {
      $search_string = $this->request->get['search'];
    }
    $data = array();
    $data['search_string'] = $search_string;
    $data['pagination_limits'] = explode(',', $this->config->get('pagination_limits'));
    $data['pagination_limit'] = $this->config->get('pagination_limit');
    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('document/search_form', $data));
  }

  public function format_ftquery($ftsearch_string)
  {
    $ftsearch_string = trim($ftsearch_string);
    $pattern = "/[ ]+/";
    $exact_match = false;
    if (preg_match('/^\".+\"$/', $ftsearch_string) === 1) {
      $ftsearch_string = preg_replace("/(^\")|(\"$)/", "", $ftsearch_string);
      $exact_match = true;
    }
    $keywords = preg_split($pattern, $ftsearch_string);

    $vowels = ['а', 'е', 'и', 'я', 'ё', 'ы', 'о', 'у', 'э', 'ю', 'й', 'ь', 's'];
    $suffix = array('очн', 'ов', 'ющ', 'ом', 'им', 'ых', 'ям', 'их', 'ъ', 'тнв');

    $json = array();
    $newkeywords = array();
    foreach ($keywords as $keyword) {
      if (!$exact_match) {
        if (mb_strlen($keyword) < 4) {
          continue;
        }
        if (mb_strlen($keyword) > 4) {
          while (in_array(mb_substr($keyword, -1), $vowels)) {
            $keyword = mb_substr($keyword, 0, -1);
          }
        }
        if (mb_strlen($keyword) >= 5) {
          foreach ($suffix as $s) {
            $suffix_len = mb_strlen($s);
            $keyword_len = mb_strlen($keyword);
            if (substr($keyword, $keyword_len - $suffix_len) == $s) {
              $cut_len = $keyword_len - $suffix_len >= 4 ? $suffix_len : $keyword_len - 4;
              $keyword = substr($keyword, 0, -$cut_len);
            }
          }
        }
        $keyword .= '*';
      }
      $newkeywords[] = $keyword;
    }
    if (count($newkeywords) > 1) {
      if ($exact_match) {
        $ftsearch_string = "\"" . implode(" ", $newkeywords) . "\"";
      } else {
        $ftsearch_string = "+" . implode(" +", $newkeywords);
      }
    } else if (count($newkeywords) === 1) {
      $ftsearch_string = implode("", $newkeywords);
    } else {
      $ftsearch_string = "";
    }

    $ftsearch_string = preg_replace("/\+-/", "-", $ftsearch_string);
    return $ftsearch_string;
  }

  public function get_documents()
  {
    $this->load->language('document/search');
    $ftsearch_string = html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8');
    $ftsearch_string = $this->format_ftquery($ftsearch_string);
    if (mb_strlen($ftsearch_string) < 4) {
      $json['total_documents'] = 0;
      $json['documents'] = $this->language->get('text_invalid_query');
      $this->response->setOutput(json_encode($json));
      return;
    }

    $this->load->model('document/search');
    $this->load->model('document/document');
    if (!empty($this->request->get['page'])) {
      $page = $this->request->get['page'];
    } else {
      $page = 1;
    }
    if (!empty($this->request->get['limit'])) {
      $limit = $this->request->get['limit'];
    } else {
      $limit = $this->config->get('pagination_limit');
    }

    $documents = array();
    $start_time = microtime(true);
    $end_time = microtime(true);
    $data['start'] = ($page - 1) * $limit;

    $data['limit'] = $limit;
    $data['ftsearch_string'] = $ftsearch_string;
    $data['is_count'] = 1;
    $documents = $this->model_document_search->getDocuments($data);
    $total_documents = $documents['total'] ?? 0;
    $documents = $documents['documents'] ?? [];
    $end_time1 = microtime(true);
    foreach ($documents as &$document) {
      $document['title'] = $this->model_document_document->getFieldDisplay($document['title_field_uid'], $document['document_uid']);
    }
    $end_time2 = microtime(true);
    $json['query_time'] = ($end_time1 - $start_time) . "\t" . ($end_time2 - $end_time1) . "\t" . ($end_time2 - $start_time);
    $json['documents'] = $documents;
    $pagination = new Pagination();
    $pagination->total = $total_documents;
    $pagination->page = $page;
    $pagination->limit = $limit;

    $json['pagination'] = $pagination->render();

    $json['total_documents'] = $total_documents;
    $json['text_total_documents'] = $this->language->get('text_total_documents');
    $json['text_show_documents'] = $this->language->get('text_show_documents');

    $this->response->setOutput(json_encode($json));
  }
}
