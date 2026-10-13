<?php

/**
 * @file
 */

require_once 'autologin_civi_tokens.civix.php';
// phpcs:disable
use Civi\Token\Event\TokenValueEvent;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Civi\Token\Event\TokenRegisterEvent;
use CRM_AutologinCiviTokens_ExtensionUtil as E;
// phpcs:enable

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function autologin_civi_tokens_civicrm_config(&$config): void {
  _autologin_civi_tokens_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function autologin_civi_tokens_civicrm_install(): void {
  _autologin_civi_tokens_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function autologin_civi_tokens_civicrm_enable(): void {
  _autologin_civi_tokens_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
// Function autologin_civi_tokens_civicrm_preProcess($formName, &$form): void {
//
// }.

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function autologin_civi_tokens_civicrm_navigationMenu(&$menu): void {
  _autologin_civi_tokens_civix_insert_navigation_menu($menu, 'Mailings', [
    'label' => E::ts('Autologin civicrm settings'),
    'name' => 'autologin_civicrm_settings',
    'url' => 'civicrm/autologin_civi_tokens/setting',
    'permission' => 'administer CiviCRM',
    'operator' => 'AND',
    'separator' => 1,
    'active' => 1,
  ]);
  _autologin_civi_tokens_civix_navigationMenu($menu);
}

/**
 * Add token services to the container.
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function autologin_civi_tokens_civicrm_container(ContainerBuilder $container) {
  if (defined('CIVICRM_UF') && CIVICRM_UF == 'Drupal8') {
    $container->addResource(new FileResource(__FILE__));
    $container->findDefinition('dispatcher')->addMethodCall('addListener',
      ['civi.token.list', 'autologin_civi_tokens_register_tokens']
    )->setPublic(TRUE);
    $container->findDefinition('dispatcher')->addMethodCall('addListener',
      ['civi.token.eval', 'autologin_civi_tokens_evaluate_tokens']
    )->setPublic(TRUE);
    $container->findDefinition('dispatcher')->addMethodCall('addListener',
      ['civi.token.render', 'autologin_civi_tokens_fix_render']
    )->setPublic(TRUE);
  }
}

/**
 *
 */
function autologin_civi_tokens_register_tokens(TokenRegisterEvent $e) {
  $e->entity('autologin_civi_tokens')->register('username_drupal', ts('Drupal username'));

  $config = \Drupal::config('autologin_civi_tokens.settings');

  $config_civi = CRM_Core_Config::singleton();

  if (CIVICRM_UF == 'Drupal8') {
    if (\Drupal::moduleHandler()->moduleExists("auto_login_url")) {
      $e->entity('autologin_civi_tokens')
        ->register('test_page_front', ts('Autologin to front page'))
        ->register('base_site', ts('Site to connect to'));

      if (Civi::settings()->get('autologin_civi_tokens_webform')) {
        $query = \Drupal::entityQuery('webform');
        if (Civi::settings()->get('autologin_civi_tokens_webform_open') == TRUE) {
          // List opened webform.
          $condition_or = $query->orConditionGroup();
          $condition_or->condition('status', 'open');

          // And also scheduled webform if we are between open and close date.
          $condition_and = $query->andConditionGroup();
          $now = new DrupalDateTime('now');
          $condition_and->condition('status', 'scheduled');
          $condition_and->condition('open', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<=');
          $condition_and->condition('close', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>');
          $condition_or->condition($condition_and);
          $query->condition($condition_or);

        }
        // ->condition('category', '')
        $webforms = $query->execute();
        $b = \Drupal::entityTypeManager()->getStorage('webform');
        $c = $b->loadMultiple($webforms);

        foreach ($c as $webform) {
          $e->entity('autologin_civi_tokens_webform')->register($webform->id(), $webform->get('title'));
        }
      }

      if (Civi::settings()->get('autologin_civi_tokens_webform_submission')) {
        $webforms_sub = \Drupal::entityQuery('webform_submission')->execute();
        // $b=\Drupal::entityTypeManager()->getStorage('webform_submission');
        // $c=$b->loadMultiple($webforms_sub);
        $open_only = Civi::settings()->get('autologin_civi_tokens_webform_submission_open');
        foreach ($webforms_sub as $webform_sub) {
          $wfr = \Drupal::entityTypeManager()->getStorage('webform_submission')
            ->load($webform_sub);
          // ->getWebform();
          $a = \Drupal::entityTypeManager()->getStorage('webform_submission')
            ->load($webform_sub);
          // Webform opened ?
          if ((($open_only) && $wfr->getWebform()->status()) || (!$open_only)) {
            $e->entity('autologin_civi_tokens_submission')->register($wfr->id(), $wfr->getWebform()->get('title'));
          }
        }
      }

      if (Civi::settings()->get('autologin_civi_tokens_view')) {
        $query = \Drupal::entityQuery('view');
        $or_cond = $query->orConditionGroup();
        // @todo config a mettre a jour.
        if ($config->get('view_filter')) {
          foreach ($config->get('view_tags') as $key) {
            $or_cond->condition('tag', $key, 'CONTAINS');
          }
          // ->condition('tag', 'default')
          $query->condition($or_cond);
        }
        $views = $query->execute();
        $b = \Drupal::entityTypeManager()->getStorage('view');
        $c = $b->loadMultiple($views);

        foreach ($c as $view) {
          foreach ($view->get('display') as $disp) {
            // $list_tokens["autologin.view_" . $view->get('id') . '$' . $disp['id']] = $view->get('label') . ":" . $disp['display_title'] . " :: Autologin View";
            if (isset($disp['display_options']['path'])) {
              // $list_tokens["aluc_view." . $view->get('id') . '_S_E_P_' . $disp['id']] = $view->get('label') . ":" . $disp['display_title'] . " :: Autologin View";
              $e->entity('autologin_civi_tokens_view')->register($view->get('id') . '_S_E_P_' . $disp['id'], $view->get('label') . ":" . $disp['display_title']);
            }
          }
        }
      }
    }
  }
}

/**
 * Nettoyage au rendu :
 * 1. les tokens retournent des URLs SANS scheme (convention : le mailing
 *    préfixe `https://`), mais par sécurité on corrige toujours un éventuel
 *    `https://https://...` hérité d'anciens templates ;
 * 2. les suffixes `?...` écrits dans le mailing juste après un token
 *    autologin (ex. `{autologin_civi_tokens_webform.X}?last_name={contact.last_name}`)
 *    ont déjà été évalués par contact et inclus dans la destination stockée
 *    en base (donc portés par le hash au login). Ils restent toutefois
 *    accolés à l'URL de login dans le corps rendu
 *    (`/autologinurl/UID/HASH?last_name=...`) : inutile et moins secure
 *    (données perso en clair dans l'URL). On les supprime pour ne retourner
 *    que l'URL avec le hash, sans paramètres — sans jamais avaler une
 *    balise Smarty accolée (ex. `{/capture}`, conservée).
 */
function autologin_civi_tokens_fix_render(\Civi\Token\Event\TokenRenderEvent $e) {
  // Garde anti-récursion : le nettoyage ré-évalue les templates via un
  // TokenProcessor secondaire, qui redéclenche 'civi.token.render'.
  if (!empty($GLOBALS['autologin_civi_tokens_in_render'])) {
    return;
  }
  if (!empty($e->string) && preg_match('#https?://https?://#i', $e->string)) {
    $e->string = preg_replace('#https?://(https?://)#i', '$1', $e->string);
  }
  if (empty($e->string) || strpos($e->string, '/autologinurl/') === FALSE) {
    return;
  }
  $GLOBALS['autologin_civi_tokens_in_render'] = TRUE;
  try {
    autologin_civi_tokens_strip_login_url_suffixes($e);
  }
  catch (\Throwable $ex) {
    \Civi::log()->warning('autologin_civi_tokens: échec nettoyage URL login: {msg}', [
      'msg' => $ex->getMessage(),
    ]);
  }
  finally {
    $GLOBALS['autologin_civi_tokens_in_render'] = FALSE;
  }
}

/**
 * Supprime les suffixes restés accolés aux URLs de login dans le corps rendu.
 *
 * Les valeurs de contact non URL-encodées peuvent contenir des espaces
 * (ex. `?tel=78 37 23 07` rendu tel quel par le TokenProcessor principal).
 * Un simple `preg_replace` s'arrêtant au premier espace laisserait alors des
 * résidus (` 37 23 07`, voire `%2037...` une fois encodés par le routeur mail)
 * concaténés au hash. On procède donc en deux temps :
 * 1. suppression exacte : chaque suffixe `?...` du template original est
 *    ré-évalué (rendu brut, sans encodage) pour ce contact, et retiré tel quel
 *    derrière l'URL de login (variantes `&amp;` / `%20` incluses) — ainsi que
 *    derrière l'URL de repli marquée (`?autologin_failed=1`) et l'URL de base
 *    nue en cas d'échec de génération ;
 * 2. filets de sécurité génériques : premier chunk `?...` puis restes
 *    `%20...` collés directement au hash (ne peuvent pas être du texte
 *    légitime, séparé lui par une vraie espace).
 * Les balises Smarty accolées (ex. `{/capture}`) ne sont jamais avalées :
 * elles stoppent la capture du suffixe et sont conservées.
 */
function autologin_civi_tokens_strip_login_url_suffixes(\Civi\Token\Event\TokenRenderEvent $e) {
  // Ancres du cas d'échec : URL de base nue et URL de repli marquée, SANS
  // scheme (les tokens retournent des URLs sans scheme).
  $site_base_slash = '';
  $fallback_anchor = '';
  try {
    $b = CRM_Utils_System::baseURL();
    $p = parse_url($b);
    if (!empty($p['scheme']) && !empty($p['host'])) {
      $site_base_slash = autologin_civi_tokens_strip_url_scheme($p['scheme'] . '://' . $p['host'] . (!empty($p['port']) ? ':' . $p['port'] : '') . '/');
      $fallback_anchor = autologin_civi_tokens_fallback_url($site_base_slash);
    }
  }
  catch (\Throwable $ex) {
    // Pas bloquant : on garde au moins le nettoyage ancré login.
  }

  // 1) Suppression exacte du suffixe évalué pour ce contact.
  try {
    $msg_string = is_array($e->message) ? ($e->message['string'] ?? '') : (string) $e->message;
    if ($msg_string !== '' && !empty($e->row)) {
      // Extraction partagée : même découpage que côté eval (balises Smarty
      // exclues, donc jamais supprimées ici).
      $found = autologin_civi_tokens_extract_query_templates($msg_string);
      if (!empty($found)) {
        $tp = $e->getTokenProcessor();
        $row = $e->row;
        $row_context = $row->tokenProcessor->rowContexts[$row->tokenRow] ?? [];
        $base_context = (isset($tp->context) && is_array($tp->context)) ? $tp->context : [];
        $raws = [];
        foreach ($found as $item) {
          $raw = autologin_civi_tokens_render_query_template_raw($item['query'], $row_context, $base_context);
          if ($raw === '') {
            continue;
          }
          $raws[] = ['entity' => $item['entity'], 'field' => $item['field'], 'raw' => $raw];
        }
        foreach ($raws as $item) {
          $variants = [
            $item['raw'],
            str_replace('&', '&amp;', $item['raw']),
            str_replace(' ', '%20', $item['raw']),
            str_replace(['&', ' '], ['&amp;', '%20'], $item['raw']),
          ];
          $variants = array_unique(array_filter($variants));
          // Les plus longs d'abord (variantes combinées avant variantes simples).
          usort($variants, function ($a, $b) {
            return strlen($b) - strlen($a);
          });
          // `base_site` (et `username_drupal`) ne passent jamais par
          // createLoginUrl : un `?...` volontaire après `{...base_site}`
          // ne doit pas être touché via l'ancre base nue.
          $is_plain_base_token = ($item['entity'] === 'autologin_civi_tokens'
            && in_array($item['field'], ['base_site', 'username_drupal'], TRUE));
          foreach ($variants as $variant) {
            $e->string = preg_replace(
              '#(/autologinurl/\d+/[A-Za-z0-9_\-]+)' . preg_quote($variant, '#') . '#',
              '$1',
              $e->string
            );
            if ($is_plain_base_token) {
              continue;
            }
            if ($fallback_anchor !== '') {
              $e->string = preg_replace(
                '#' . preg_quote($fallback_anchor, '#') . preg_quote($variant, '#') . '#',
                $fallback_anchor,
                $e->string
              );
            }
            if ($site_base_slash !== '') {
              $e->string = preg_replace(
                '#' . preg_quote($site_base_slash, '#') . preg_quote($variant, '#') . '#',
                $site_base_slash,
                $e->string
              );
            }
          }
        }
      }
    }
  }
  catch (\Throwable $ex) {
    \Civi::log()->warning('autologin_civi_tokens: suppression exacte impossible: {msg}', [
      'msg' => $ex->getMessage(),
    ]);
  }

  // 2) Filets de sécurité génériques (les `{` sont exclus pour préserver
  // les balises Smarty accolées, ex. `{/capture}`).
  // Premier chunk `?...` (sans espaces) après une URL de login.
  $e->string = preg_replace('#(/autologinurl/\d+/[A-Za-z0-9_\-]+)(\?[^\s"\'<>\\{]*)#', '$1', $e->string);
  // Restes `%20...` collés directement au hash (ex. `HASH%2078%2037...`) :
  // les valeurs à espaces non encodées, partiellement nettoyées puis
  // encodées par la chaîne d'envoi.
  $e->string = preg_replace('#(/autologinurl/\d+/[A-Za-z0-9_\-]+)(%20[^\s"\'<>\\{]*)#', '$1', $e->string);
  // Mêmes filets après le marqueur de repli (ex.
  // `monsite.org/?autologin_failed=1?tel=78` ou `...=1%2037...`).
  if ($fallback_anchor !== '') {
    $e->string = preg_replace('#' . preg_quote($fallback_anchor, '#') . '(\?[^\s"\'<>\\{]*)#', $fallback_anchor, $e->string);
    $e->string = preg_replace('#' . preg_quote($fallback_anchor, '#') . '(%20[^\s"\'<>\\{]*)#', $fallback_anchor, $e->string);
  }
}

/**
 * Retire le scheme (`http://`, `https://`) d'une URL.
 *
 * Convention des mailings : les tokens autologin retournent des URLs SANS
 * scheme (ex. `monsite.org/autologinurl/1/abc`), et c'est le template du
 * mailing qui préfixe `https://` devant le token
 * (ex. `<a href="https://{autologin_civi_tokens_webform.mon_form}">`).
 * Cela évite tout doublon `https://https://...`.
 */
function autologin_civi_tokens_strip_url_scheme($url) {
  return preg_replace('#^https?://#i', '', (string) $url);
}

/**
 * Tronque un suffixe `?...` avant toute balise Smarty.
 *
 * Le suffixe peut contenir des tokens Civi (`{contact.last_name}`, avec
 * accolades) qu'il faut conserver, mais il ne doit jamais avaler une balise
 * Smarty directement accolée (ex. `?tel={contact.phone}{/capture}` : le
 * `{/capture}` doit rester dans le corps du mailing). On avance caractère
 * par caractère : à profondeur 0, un `{` ouvrant un token Civi
 * (`{entité.champ...}`) est consommé jusqu'à son `}` fermant, tout autre
 * `{` (Smarty : `{/capture}`, `{$var}`, `{if ...}`, ...) stoppe la capture.
 *
 * @return string
 *   Ex. '?tel={contact.phone}&cid={contact.id}' pour
 *   '?tel={contact.phone}&cid={contact.id}{/capture}'.
 */
function autologin_civi_tokens_truncate_before_smarty($query) {
  $len = strlen($query);
  $out = '';
  $i = 0;
  while ($i < $len) {
    $c = $query[$i];
    if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r" || $c === "\f" || $c === "\v" || $c === '"' || $c === "'" || $c === '<' || $c === '>') {
      break;
    }
    if ($c === '{') {
      // Token Civi (`{entité.champ}`) : on le conserve en entier.
      if (preg_match('/\A\{[A-Za-z_][A-Za-z0-9_:\-]*\./', substr($query, $i, 64))) {
        $end = strpos($query, '}', $i);
        if ($end === FALSE) {
          break;
        }
        $out .= substr($query, $i, $end - $i + 1);
        $i = $end + 1;
        continue;
      }
      // Balise Smarty (ex. `{/capture}`) : on tronque ici, elle est conservée.
      break;
    }
    $out .= $c;
    $i++;
  }
  return $out;
}

/**
 * Extrait, depuis une chaîne brute de mailing, les suffixes `?...` placés
 * juste après chaque token autologin.
 *
 * Extraction partagée par la collecte côté `civi.token.eval` et par le
 * nettoyage côté `civi.token.render` (les deux doivent s'accorder) :
 * les tokens Civi `{contact.xxx}` sont conservés dans le suffixe, les
 * balises Smarty (`{/capture}`, ...) sont exclues et préservées.
 *
 * Ex. pour `{autologin_civi_tokens_webform.X}?tel={contact.phone}{/capture}` :
 *   [['entity' => 'autologin_civi_tokens_webform', 'field' => 'X',
 *     'query' => '?tel={contact.phone}']]
 *
 * @return array
 */
function autologin_civi_tokens_extract_query_templates($raw_string) {
  $found = [];
  $pattern = '/\{(autologin_civi_tokens_webform|autologin_civi_tokens_view|autologin_civi_tokens_submission|autologin_civi_tokens)\.([A-Za-z0-9_:\.\-]+)\}(\?[^\s"\'<>]*)/';
  if (preg_match_all($pattern, $raw_string, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
      // En HTML, & est encodé &amp; : on décode pour retrouver le template réel.
      $query = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
      // ... sans jamais avaler une balise Smarty accolée (ex. {/capture}).
      $query = autologin_civi_tokens_truncate_before_smarty($query);
      if ($query === '' || $query === '?') {
        continue;
      }
      $found[] = ['entity' => $m[1], 'field' => $m[2], 'query' => $query];
    }
  }
  return $found;
}

/**
 * Collecte, depuis le corps brut du mailing, les suffixes `?...` placés
 * juste après chaque token autologin.
 *
 * Ex. dans le mailing :
 *   {autologin_civi_tokens_webform.benevoles_puyolles_2026}?last_name={contact.last_name}&cid={contact.id}
 *
 * @return array
 *   Ex. ['autologin_civi_tokens_webform' => ['benevoles_puyolles_2026' => '?last_name={contact.last_name}&cid={contact.id}']]
 */
function autologin_civi_tokens_collect_url_query_templates(TokenValueEvent $e) {
  $templates = [];
  $raw_strings = [];

  $tp = $e->getTokenProcessor();
  // 1) Messages déjà enregistrés dans le TokenProcessor (body_html, body_text, subject...).
  foreach (['body_html', 'body_text', 'body', 'subject'] as $msg_name) {
    try {
      $msg = $tp->getMessage($msg_name);
      if (!empty($msg['string'])) {
        $raw_strings[] = $msg['string'];
      }
    }
    catch (\Throwable $ex) {
      // Message inexistant, on ignore.
    }
  }

  // 2) Fallback : relire le mailing source (utile si getMessage() indisponible).
  if (empty($raw_strings) && !empty($tp->context['mailingId'])) {
    try {
      $mailing = civicrm_api4('Mailing', 'get', [
        'select' => ['body_html', 'body_text', 'subject'],
        'where' => [['id', '=', $tp->context['mailingId']]],
        'limit' => 1,
      ])->first();
      if (!empty($mailing['body_html'])) {
        $raw_strings[] = $mailing['body_html'];
      }
      if (!empty($mailing['body_text'])) {
        $raw_strings[] = $mailing['body_text'];
      }
      if (!empty($mailing['subject'])) {
        $raw_strings[] = $mailing['subject'];
      }
    }
    catch (\Throwable $ex) {
      // Pas bloquant.
    }
  }

  foreach ($raw_strings as $raw) {
    // Extraction partagée (tokens Civi conservés, balises Smarty exclues).
    foreach (autologin_civi_tokens_extract_query_templates($raw) as $item) {
      if (!isset($templates[$item['entity']][$item['field']])) {
        $templates[$item['entity']][$item['field']] = $item['query'];
      }
    }
  }
  return $templates;
}

/**
 * Rendu brut d'un suffixe `?a={contact.last_name}&b=...` pour un contact
 * donné, via un TokenProcessor secondaire (supporte tous les tokens Civi,
 * pas seulement contact.*).
 *
 * Contrairement à autologin_civi_tokens_evaluate_query_template(), aucune
 * normalisation / URL-encodage n'est appliquée : les valeurs gardent leurs
 * espaces tels que le TokenProcessor principal les substitue dans le corps
 * du mailing. Sert à retrouver (puis supprimer au rendu) le suffixe exact
 * resté accolé à l'URL de login.
 *
 * @return string
 *   Ex. '?tel=78 37 23 07&cid=123' (vide si $template vide).
 */
function autologin_civi_tokens_render_query_template_raw($template, array $row_context, array $base_context) {
  $template = trim((string) $template);
  if ($template === '' || $template === '?') {
    return '';
  }
  if ($template[0] !== '?' && $template[0] !== '&') {
    $template = '?' . ltrim($template, '?&');
  }
  try {
    // Garde anti-récursion : le processeur secondaire redéclenche
    // 'civi.token.eval' (et 'civi.token.render'), donc nos propres
    // listeners doivent s'ignorer.
    $GLOBALS['autologin_civi_tokens_nested'] = TRUE;

    $proc = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), $base_context);
    $proc->addMessage('autologin_query', $template, 'text/plain');
    $new_row = $proc->addRow($row_context);
    $proc->evaluate();
    $rendered = $proc->render('autologin_query', $new_row);
  }
  catch (\Throwable $ex) {
    \Civi::log()->warning('autologin_civi_tokens: échec évaluation query "{tpl}": {msg}', [
      'tpl' => $template,
      'msg' => $ex->getMessage(),
    ]);
    $rendered = html_entity_decode($template, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  finally {
    $GLOBALS['autologin_civi_tokens_nested'] = FALSE;
  }

  return html_entity_decode(trim((string) $rendered), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Evalue un suffixe `?a={contact.last_name}&b=...` pour un contact donné,
 * via un TokenProcessor secondaire (supporte tous les tokens Civi,
 * pas seulement contact.*), puis reconstruit une query-string proprement
 * URL-encodée (RFC3986).
 *
 * @return string
 *   Ex. '?last_name=Dupont&cid=123' (vide si $template vide).
 */
function autologin_civi_tokens_evaluate_query_template($template, $row, TokenValueEvent $event) {
  $main_tp = $event->getTokenProcessor();
  $row_context = $row->tokenProcessor->rowContexts[$row->tokenRow] ?? [];
  $base_context = (isset($main_tp->context) && is_array($main_tp->context)) ? $main_tp->context : [];

  // Rendu brut (espaces conservés), puis normalisation + encodage pour le
  // stockage en base (destination portée par le hash).
  $rendered = autologin_civi_tokens_render_query_template_raw($template, $row_context, $base_context);
  if ($rendered === '' || $rendered === '?') {
    return '';
  }
  $rendered = ltrim($rendered, '?&');
  if ($rendered === '') {
    return '';
  }
  $pairs = [];
  foreach (explode('&', $rendered) as $chunk) {
    if ($chunk === '') {
      continue;
    }
    $kv = explode('=', $chunk, 2);
    $k = rawurldecode($kv[0]);
    $v = isset($kv[1]) ? rawurldecode($kv[1]) : '';
    $pairs[$k] = $v;
  }
  if (empty($pairs)) {
    return '';
  }
  return '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Concatène une query évaluée à une destination Drupal.
 */
function autologin_civi_tokens_append_query($destination, $evaluated_query) {
  $evaluated_query = (string) $evaluated_query;
  if ($evaluated_query === '') {
    return $destination;
  }
  $q = ltrim($evaluated_query, '?&');
  if ($q === '') {
    return $destination;
  }
  $sep = (strpos($destination, '?') === FALSE) ? '?' : '&';
  return $destination . $sep . $q;
}

/**
 * Evalue les tokens autologin. Les suffixes `?...` placés dans le mailing
 * juste après un token autologin (ex. `{autologin_civi_tokens_webform.X}?last_name={contact.last_name}&...`)
 * sont évalués par contact puis inclus dans la destination stockée AVANT
 * création du hash auto_login_url, de sorte que l'URL complète (avec
 * paramètres pré-remplis) soit portée par le hash et rejouée au login.
 * En revanche l'URL retournée / envoyée ne contient que le hash
 * (`/autologinurl/UID/HASH`), sans les paramètres (voir
 * autologin_civi_tokens_fix_render() qui supprime le suffixe resté dans le
 * corps rendu).
 * Si la génération est impossible pour un contact (pas de compte Drupal,
 * webform/vue introuvable, service auto_login_url en échec, ...), le token
 * reçoit l'URL de base marquée `?autologin_failed=1` (cf.
 * autologin_civi_tokens_fallback_url()), testable en Smarty pour masquer le
 * bloc concerné — au lieu de laisser le token non résolu ou de faire
 * échouer tout le mailing.
 */
function autologin_civi_tokens_evaluate_tokens(TokenValueEvent $e) {
  // Garde anti-récursion pour l'évaluation secondaire des query templates.
  if (!empty($GLOBALS['autologin_civi_tokens_nested'])) {
    return;
  }
  $tokens = $e->getTokenProcessor()->getMessageTokens();
  $config_civi = CRM_Core_Config::singleton();

  $config = \Drupal::config('autologin_civi_tokens.settings');

  $debug = Civi::settings()->get('autologin_civi_tokens_debug');

  $cids = [];
  $mailingJobId = '';

  foreach ($e->getTokenProcessor()->rowContexts as $context) {
    $cids[] = $context['contactId'];
  }
  $mailingJobId = $context['mailingJobId'];

  foreach ($e->getRows() as $row) {
    $aa = $row;
    $bb = $row->tokenProcessor->rowContexts[$row->tokenRow];
  }

  if ($debug) {
    \Drupal::logger('autologin_civi_tokens_tokenValues')->notice('tokens: @tokens, mailingJobId: @mailingJobId, mailingid: @mailingid', [
      '@tokens' => print_r($tokens, TRUE) ,
      '@mailingJobId' => print_r($mailingJobId, TRUE),
      '@mailigid' => print_r($e->getTokenProcessor()->context['mailingId'], TRUE),
    ]);
  }

  if (count(preg_grep('/autologin_civi_tokens/', array_keys($tokens)))) {
    $civicrm = \Drupal::service('civicrm');
    $base_url = CRM_Utils_System::baseURL();
    // base_url peut contenir un chemin, en particulier les batchs Civicrm.
    $r = parse_url($base_url);
    // Recontruit juste l'URL du site.
    $site_url = $r['scheme'] . '://' . $r['host'] . (!empty($r['port']) ? ':' . $r['port'] : '');
    // Repli uniforme si un auto-login ne peut pas être généré (testable en Smarty).
    $fallback_url = autologin_civi_tokens_fallback_url($site_url);

    $alu_service = \Drupal::service('auto_login_url.create');

    $trace = Civi::settings()->get('autologin_civi_tokens_absurl');

    // Suffixes `?...` écrits dans le mailing après chaque token autologin
    // (ex. {autologin_civi_tokens_webform.X}?last_name={contact.last_name}&...).
    // Ils seront évalués par contact et inclus dans la destination AVANT hash.
    $query_templates = autologin_civi_tokens_collect_url_query_templates($e);
    if ($debug && !empty($query_templates)) {
      \Drupal::logger('autologin_civi_tokens_tokenValues')->notice('query_templates: @qt', [
        '@qt' => print_r($query_templates, TRUE),
      ]);
    }

    foreach ($e->getRows() as $row) {
      $cid = $row->tokenProcessor->rowContexts[$row->tokenRow]['contactId'];
      $params = [
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $cid,
      ];
      // @todo Check error if duplicate e-email!!
      $result = civicrm_api('UFMatch', 'get', $params);
      if (!$result['is_error'] && isset($result['values'][0]['uf_id'])) {
        $drupalinfo = \Drupal::entityTypeManager()->getStorage('user')->load($result['values'][0]['uf_id']);

        if ($debug) {
          \Drupal::logger('autologin_civi_tokens(0)')->notice('cid: @cid, tokens: @tokens, result: @result, drupal id: @drupalinfo ', [
            '@cid' => print_r($cid, TRUE) ,
            '@tokens' => print_r($tokens, TRUE) ,
            '@result' => print_r($result, TRUE) ,
            '@drupalinfo' => $drupalinfo ? $drupalinfo->id() : '????',
          ]);
        }

        // uf_id does not exist.
        if ($drupalinfo === NULL) {
          \Drupal::logger('autologin_civi_tokens')->error("Contact_id " . $cid . ", uf_id " . $result['values'][0]['uf_id'] . " don't exist. Mail not send");
          // Pas d'auto-login possible : repli sur l'URL de base (testable en Smarty).
          autologin_civi_tokens_setFallbackTokens($row, $tokens, $fallback_url);
          // Remove this from mailing
          // unset($values[$cid]);.
          continue;
        }

        if (isset($tokens['autologin_civi_tokens'])) {
          foreach ($tokens['autologin_civi_tokens'] as $token) {
            switch ($token) {
              case 'base_site':
                autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', 'base_site', $site_url . '/');
                break;

              case 'page_front':
              case 'test_page_front':
                try {
                  $front_dest = '/';
                  if (!empty($query_templates['autologin_civi_tokens'][$token])) {
                    $evaluated = autologin_civi_tokens_evaluate_query_template($query_templates['autologin_civi_tokens'][$token], $row, $e);
                    $front_dest = autologin_civi_tokens_append_query($front_dest, $evaluated);
                  }
                  $login_url = autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_civi_tokens_absurl'), $drupalinfo->id(), $front_dest, $site_url);
                }
                catch (\Throwable $ex) {
                  \Drupal::logger('autologin_civi_tokens')->warning('Echec token @token (cid @cid): @msg. Repli sur URL de base.', [
                    '@token' => $token,
                    '@cid' => $cid,
                    '@msg' => $ex->getMessage(),
                  ]);
                  $login_url = $fallback_url;
                }
                autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', $token, $login_url);
                break;

              case 'username_drupal':
                autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', 'username_drupal', $drupalinfo->getAccountName());
                break;
            }
          }
        }

        if (isset($tokens['autologin_civi_tokens_webform'])) {
          foreach ($tokens['autologin_civi_tokens_webform'] as $token) {
            try {
              $url = \Drupal::entityTypeManager()->getStorage('webform')
                ->load(current(\Drupal::entityQuery('webform')->condition('id', $token)->execute()))
                ->toUrl()->toString();
              // Destination complète (avec paramètres évalués par contact) :
              // c'est elle qui est stockée en base via create() et donc portée
              // par le hash pour être rejouée au login.
              $full_destination = $url;
              if (!empty($query_templates['autologin_civi_tokens_webform'][$token])) {
                $evaluated = autologin_civi_tokens_evaluate_query_template($query_templates['autologin_civi_tokens_webform'][$token], $row, $e);
                $full_destination = autologin_civi_tokens_append_query($url, $evaluated);
              }
              // create() ne retourne que l'URL de login (/autologinurl/UID/HASH),
              // sans les paramètres (plus secure, pas utile en clair) : le
              // suffixe `?...` resté dans le corps du mailing est supprimé au
              // rendu par autologin_civi_tokens_fix_render().
              $login_url = autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_civi_tokens_absurl'), $drupalinfo->id(), $full_destination, $site_url);
            }
            catch (\Throwable $ex) {
              \Drupal::logger('autologin_civi_tokens')->warning('Echec token webform @token (cid @cid): @msg. Repli sur URL de base.', [
                '@token' => $token,
                '@cid' => $cid,
                '@msg' => $ex->getMessage(),
              ]);
              $login_url = $fallback_url;
            }
            autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_webform', $token, $login_url);
          }
        }

        if (isset($tokens['autologin_civi_tokens_submission'])) {
          foreach ($tokens['autologin_civi_tokens_submission'] as $token) {
            try {
              $aa = $token;
              // $ssql = "SELECT sforms.sid FROM {webform_submissions} sforms where sforms.nid = $nid and
              //   sforms.uid = $drupalinfo->uid order by sid desc limit 1";
              // $sq_result = \Drupal::database()->query($ssql);
              // $sid = $sq_result->fetchField();
              // $url = "node/$nid/submission/$sid/edit";
              $url = \Drupal::entityTypeManager()->getStorage('webform_submission')
                ->load($token)->url();
              if (!empty($query_templates['autologin_civi_tokens_submission'][$token])) {
                $evaluated = autologin_civi_tokens_evaluate_query_template($query_templates['autologin_civi_tokens_submission'][$token], $row, $e);
                $url = autologin_civi_tokens_append_query($url, $evaluated);
              }
              $login_url = autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_civi_tokens_absurl'), $drupalinfo->id(), $url, $site_url);
            }
            catch (\Throwable $ex) {
              \Drupal::logger('autologin_civi_tokens')->warning('Echec token submission @token (cid @cid): @msg. Repli sur URL de base.', [
                '@token' => $token,
                '@cid' => $cid,
                '@msg' => $ex->getMessage(),
              ]);
              $login_url = $fallback_url;
            }
            autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_submission', $token, $login_url);
          }

        }

        if (isset($tokens['autologin_civi_tokens_view'])) {
          foreach ($tokens['autologin_civi_tokens_view'] as $token) {
            try {
              $aa = $token;
              [$view, $display] = explode('_S_E_P_', $token);
              $url = '/' . \Drupal::entityTypeManager()->getStorage('view')
                ->load(current(\Drupal::entityQuery('view')->condition('id', $view)->execute()))
                ->get('display')[$display]['display_options']['path'];
              if (!empty($query_templates['autologin_civi_tokens_view'][$token])) {
                $evaluated = autologin_civi_tokens_evaluate_query_template($query_templates['autologin_civi_tokens_view'][$token], $row, $e);
                $url = autologin_civi_tokens_append_query($url, $evaluated);
              }
              $login_url = autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_civi_tokens_absurl'), $drupalinfo->id(), $url, $site_url);
            }
            catch (\Throwable $ex) {
              \Drupal::logger('autologin_civi_tokens')->warning('Echec token view @token (cid @cid): @msg. Repli sur URL de base.', [
                '@token' => $token,
                '@cid' => $cid,
                '@msg' => $ex->getMessage(),
              ]);
              $login_url = $fallback_url;
            }
            autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_view', $token, $login_url);
          }

        }

        /* if ($trace) {
        \Drupal::logger('autologin_civi_tokens_civi_tokens')->notice(' cid: @cid, val: @val, url: @url, cur_token_raw: @cur_token_raw ', array(
        '@cid' => $cid,
        '@val' => $values[$cid][$tok_key.'.'.$tmp_key],
        '@url' => $url,
        '@cur_token_raw' => $cur_token_raw
        ));
        } */

      }
      else {
        // Aucun compte Drupal pour ce contact : pas d'auto-login possible.
        // Repli sur l'URL de base (testable en Smarty).
        autologin_civi_tokens_setFallbackTokens($row, $tokens, $fallback_url);
      }
    }
  }
}

/**
 * Marqueur distinctif de l'URL de repli (échec de génération auto-login).
 *
 * Source unique de vérité, utilisée à la fois pour construire l'URL de repli
 * et pour la détecter (PHP/Smarty). Ne pas traduire : c'est une chaîne
 * technique testée via `strstr`.
 */
function autologin_civi_tokens_fallback_marker() {
  return 'autologin_failed=1';
}

/**
 * URL de repli quand l'auto-login n'a pas pu être généré.
 *
 * Retourne l'URL de base du site, SANS scheme et marquée d'une chaîne
 * distinctive (`?autologin_failed=1`) permettant de détecter simplement en
 * Smarty que la génération a échoué et de masquer le bloc concerné. Ex.
 * (le mailing préfixe `https://`, les tokens étant sans scheme) :
 *
 *   {capture assign=login_url}https://{autologin_civi_tokens_webform.mon_formulaire}{/capture}
 *   {if $login_url|strstr:"autologin_failed"}
 *     ... repli sans auto-login (ou rien) ...
 *   {else}
 *     <a href="{$login_url}">Accéder au formulaire</a>
 *   {/if}
 */
function autologin_civi_tokens_fallback_url($site_url) {
  return autologin_civi_tokens_strip_url_scheme(rtrim((string) $site_url, '/') . '/?' . autologin_civi_tokens_fallback_marker());
}

/**
 * Valeurs de repli pour un contact sans auto-login possible.
 *
 * Positionne tous les tokens d'URL auto-login demandés sur l'URL de base
 * (cf. autologin_civi_tokens_fallback_url()). `base_site` garde sa valeur
 * normale (c'est déjà l'URL de base) et `username_drupal` n'est pas une URL :
 * ils sont laissés intacts.
 */
function autologin_civi_tokens_setFallbackTokens($row, array $tokens, $fallback_url) {
  if (isset($tokens['autologin_civi_tokens'])) {
    foreach ($tokens['autologin_civi_tokens'] as $token) {
      if ($token === 'base_site' || $token === 'username_drupal') {
        continue;
      }
      autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', $token, $fallback_url);
    }
  }
  if (isset($tokens['autologin_civi_tokens_webform'])) {
    foreach ($tokens['autologin_civi_tokens_webform'] as $token) {
      autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_webform', $token, $fallback_url);
    }
  }
  if (isset($tokens['autologin_civi_tokens_submission'])) {
    foreach ($tokens['autologin_civi_tokens_submission'] as $token) {
      autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_submission', $token, $fallback_url);
    }
  }
  if (isset($tokens['autologin_civi_tokens_view'])) {
    foreach ($tokens['autologin_civi_tokens_view'] as $token) {
      autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_view', $token, $fallback_url);
    }
  }
}

/**
 * Génère une URL d'auto-login, avec repli marqué sur l'URL de base du site.
 *
 * L'URL retournée est SANS scheme (ex. `monsite.org/autologinurl/1/abc`) :
 * c'est le template du mailing qui préfixe `https://` devant le token
 * (ex. `<a href="https://{autologin_civi_tokens_webform.mon_form}">`),
 * ce qui évite tout doublon `https://https://...`.
 *
 * Ne lance jamais d'exception : si le service auto_login_url échoue
 * (rate-limit, expiration, destination invalide, uid invalide, ...) ou
 * retourne une URL inutilisable, retourne l'URL de base marquée
 * `?autologin_failed=1` (sans scheme), testable en Smarty (cf.
 * autologin_civi_tokens_fallback_url()).
 */
function autologin_civi_tokens_createLoginUrl($alu_service, $absurl, $drupalid, $path, $site_url) {
  $fallback = autologin_civi_tokens_fallback_url($site_url);
  try {
    if (!($drupalid > 0)) {
      throw new \Exception('uid invalide pour la génération auto-login');
    }
    $autoPath = $alu_service->create((int) $drupalid, (string) $path, FALSE);
    if (!is_string($autoPath) || $autoPath === '' || strpos($autoPath, '/autologinurl/') === FALSE) {
      throw new \Exception('URL auto-login invalide retournée par le service');
    }
  }
  catch (\Throwable $ex) {
    \Drupal::logger('autologin_civi_tokens')->warning('Echec génération auto-login (uid: @uid, dest: @dest): @msg. Repli sur URL de base.', [
      '@uid' => $drupalid,
      '@dest' => substr((string) $path, 0, 100),
      '@msg' => $ex->getMessage(),
    ]);
    return $fallback;
  }
  if ($absurl) {
    $urlpath = $site_url . (str_starts_with($autoPath, '/') ? $autoPath : '/' . $autoPath);
  }
  else {
    $urlpath = $autoPath;
  }
  // Sans scheme : le mailing préfixe `https://` devant le token
  // (cf. autologin_civi_tokens_strip_url_scheme()). Sans effet sur les
  // chemins relatifs.
  $urlpath = autologin_civi_tokens_strip_url_scheme($urlpath);
  if (Civi::settings()->get('autologin_civi_tokens_log_url')) {
    \Drupal::logger('autologin_civi_tokens')->notice('id: @id, path: @path, absurl: @absurl, url: @url, returnPath: @returnPath', [
      '@id' => $drupalid,
      '@path' => $path,
      '@absurl' => $absurl,
      '@url' => $autoPath,
      '@returnPath' => $urlpath,
    ]);
  }
  return $urlpath;
}

/**
 *
 */
function autologin_civi_tokens_setToken($row, $profile, $token, $value) {
  $row->format('text/html');
  $row->tokens($profile, $token, $value);
  // $row->format('text/plain');
  // $row->tokens($profile, $token, $value);
}
