<?php

require_once 'autologin_civi_tokens.civix.php';
// phpcs:disable
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
//function autologin_civi_tokens_civicrm_preProcess($formName, &$form): void {
//
//}

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
  }
}

function autologin_civi_tokens_register_tokens(\Civi\Token\Event\TokenRegisterEvent $e) {
  $e->entity('autologin_civi_tokens')->register('username_drupal', ts('Drupal username'));

  $config = \Drupal::config('autologin_civi_tokens.settings');

  $config_civi = CRM_Core_Config::singleton();

  if (CIVICRM_UF == 'Drupal8') {
     if ( \Drupal::moduleHandler()->moduleExists("auto_login_url")) {
        $e->entity('autologin_civi_tokens')
           ->register('test_page_front', ts('Autologin to front page'))
           ->register('base_site', ts('Site to connect to'));


        if (Civi::settings()->get('autologin_webform')) {
           $query = \Drupal::entityQuery('webform');
           if (Civi::settings()->get('autologin_webform_open') == TRUE) {
               //list opened webform
               $condition_or = $query->orConditionGroup();
               $condition_or->condition('status', 'open');

               // and also scheduled webform if we are between open and close date
               $condition_and = $query->andConditionGroup();
               $now=new DrupalDateTime('now');
               $condition_and->condition('status', 'scheduled');
               $condition_and->condition('open', $now->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<=');
               $condition_and->condition('close', $now->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>');
               $condition_or->condition($condition_and);
               $query->condition($condition_or);

           }
              // ->condition('category', '')
           $webforms=$query->execute();
           $b=\Drupal::entityTypeManager()->getStorage('webform');
           $c=$b->loadMultiple($webforms);

           foreach ($c as $webform) {
              // $list_tokens["autologin.webform_" . urlencode($webform->url())] = $webform->get('title') . " :: Autologin Webforms";
              //$list_tokens["aluc_wf." . $webform->id()] = $webform->get('title') . " :: Autologin Webforms";
              $e->entity('autologin_civi_tokens_webform')->register($webform->id(), $webform->get('title'));
           }
        }


        if (Civi::settings()->get('autologin_webform_submission')) {
           $webforms_sub = \Drupal::entityQuery('webform_submission')->execute();
           // $b=\Drupal::entityTypeManager()->getStorage('webform_submission');
           // $c=$b->loadMultiple($webforms_sub);

           $open_only = Civi::settings()->get('autologin_webform_submission_open');
           foreach ($webforms_sub as $webform_sub) {
              $wfr = \Drupal::entityTypeManager()->getStorage('webform_submission')
                    ->load($webform_sub);
                    // ->getWebform();
              $a=\Drupal::entityTypeManager()->getStorage('webform_submission')
                    ->load($webform_sub);
              if ((($open_only) && $wfr->getWebform()->status()) || (! $open_only)) { //Webform opened ?
                 // $list_tokens["aluc_wfs." . $wfr->id()] = $wfr->getWebform()->get('title') . " :: Autologin Webforms Submission";
                 $e->entity('autologin_civi_tokens_submission')->register($wfr->id(), $wfr->getWebform()->get('title'));
              }
           }
        }




        if (Civi::settings()->get('autologin_view')) {
           $query = \Drupal::entityQuery('view');
           $or_cond = $query->orConditionGroup();
           // TODO: config
           if ($config->get('view_filter')) {
              foreach ($config->get('view_tags') as $key) {
                 $or_cond->condition('tag', $key, 'CONTAINS');
              }
              //   ->condition('tag', 'default')
              $query->condition($or_cond);
           }
           $views=$query->execute();
           $b=\Drupal::entityTypeManager()->getStorage('view');
           $c=$b->loadMultiple($views);

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

function autologin_civi_tokens_evaluate_tokens(\Civi\Token\Event\TokenValueEvent $e) {
  $tokens = $e->getTokenProcessor()->getMessageTokens();
  $config_civi = CRM_Core_Config::singleton();

  $config = \Drupal::config('autologin_civi_tokens.settings');

  $debug = Civi::settings()->get('autologin_debug');

  $cids = [];
  $mailingJobId = '';

  foreach ($e->getTokenProcessor()->rowContexts as $context) {
     $cids[] = $context['contactId'];
  }
  $mailingJobId = $context['mailingJobId'];

  foreach ($e->getRows() as $row) {
     $aa=$row;
     $bb=$row->tokenProcessor->rowContexts[$row->tokenRow];
  }

  if ($debug) {
     \Drupal::logger('autologin_civi_tokens_tokenValues')->notice('tokens: @tokens, mailingJobId: @mailingJobId, mailingid: @mailingid', array(
        '@tokens' => print_r($tokens, true) ,
        '@mailingJobId' => print_r($mailingJobId, true) ,
        '@mailigid' => print_r($e->getTokenProcessor()->context['mailingId'], true)
     ));
  }

  if (count(preg_grep('/autologin_civi_tokens/', array_keys($tokens)))) {
     $civicrm = \Drupal::service('civicrm');
     $base_url=CRM_Utils_System::baseURL();
     // base_url peut contenir un chemin, en particulier les batchs Civicrm
     $r = parse_url($base_url);
     // recontruit juste l'URL du site
     $site_url = $r['scheme'] . '://' . $r['host'] . (!empty($r['port']) ? ':'.$r['port'] : '');

     $alu_service = \Drupal::service('auto_login_url.create');

     $trace = $config->get('trace');

     foreach ($e->getRows() as $row) {
        $cid=$row->tokenProcessor->rowContexts[$row->tokenRow]['contactId'];
        $params = array(
           'version' => 3,
           'sequential' => 1,
           'contact_id' => $cid
        );
        // TODO : Check error if duplicate e-email!!
        $result = civicrm_api('UFMatch', 'get', $params);
        if (!$result['is_error'] && isset($result['values'][0]['uf_id'])) {
           $drupalinfo = \Drupal::entityTypeManager()->getStorage('user')->load($result['values'][0]['uf_id']);

           if ($debug) {
              \Drupal::logger('autologin_civi_tokens(0)')->notice('cid: @cid, tokens: @tokens, result: @result, drupal id: @drupalinfo ', array(
                 '@cid' => print_r($cid, true) ,
                 '@tokens' => print_r($tokens, true) ,
                 '@result' => print_r($result, true) ,
                 '@drupalinfo' => $drupalinfo ? $drupalinfo->id() : '????'
              ));
           }

           # uf_id does not exist
           if ($drupalinfo === null) {
              \Drupal::logger('autologin_civi_tokens')->error("Contact_id " . $cid . ", uf_id " . $result['values'][0]['uf_id'] . " don't exist. Mail not send");
              # Remove this from mailing
              # unset($values[$cid]);
              continue;
           }

           if (isset($tokens['autologin_civi_tokens'])) {
              foreach ($tokens['autologin_civi_tokens'] as $token) {
                 switch ($token) {
                    case 'base_site':
                       autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', 'base_site',$site_url . '/');
                       break;
                    case 'page_front':
                       autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', 'page_front',
                          autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_url'), $drupalinfo->id(), '/', $site_url ));
                       break;
                    case 'username_drupal':
                       autologin_civi_tokens_setToken($row, 'autologin_civi_tokens', 'username_drupal', $drupalinfo->getAccountName());
                       break;
                 }
              }
           }

           if (isset($tokens['autologin_civi_tokens_webform'])) {
              foreach ($tokens['autologin_civi_tokens_webform'] as $token) {
                 $url = \Drupal::entityTypeManager()->getStorage('webform')
                    ->load(current(\Drupal::entityQuery('webform')->condition('id', $token)->execute()))
                    ->toUrl()->toString();
                    autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_webform', $token,
                    autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_url'), $drupalinfo->id(), $url , $site_url ));
              }
           }

           if (isset($tokens['autologin_civi_tokens_submission'])) {
              foreach ($tokens['autologin_civi_tokens_submission'] as $token) {
                 $aa=$token;
                 // $ssql = "SELECT sforms.sid FROM {webform_submissions} sforms where sforms.nid = $nid and
                 //   sforms.uid = $drupalinfo->uid order by sid desc limit 1";

                 // $sq_result = \Drupal::database()->query($ssql);
                 // $sid = $sq_result->fetchField();
                 // $url = "node/$nid/submission/$sid/edit";
                 $url=\Drupal::entityTypeManager()->getStorage('webform_submission')
                    ->load($token)->url();
                 autologin_civi_tokens_setToken($row, 'webform_submission', $token,
                    autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_url'), $drupalinfo->id(), $url , $site_url ));
              }

           }

           if (isset($tokens['autologin_civi_tokens_view'])) {
              foreach ($tokens['autologin_civi_tokens_view'] as $token) {
                 $aa=$token;
                 list($view,$display)=explode('_S_E_P_', $token);
                    $url = '/' . \Drupal::entityTypeManager()->getStorage('view')
                    ->load(current(\Drupal::entityQuery('view')->condition('id', $view)->execute()))
                    ->get('display')[$display]['display_options']['path'];
                 autologin_civi_tokens_setToken($row, 'autologin_civi_tokens_view', $token,
                    autologin_civi_tokens_createLoginUrl($alu_service, Civi::settings()->get('autologin_url'), $drupalinfo->id(), $url , $site_url ));
              }

           }

           /* if ($trace) {
              \Drupal::logger('autologin_civi_tokens')->notice(' cid: @cid, val: @val, url: @url, cur_token_raw: @cur_token_raw ', array(
                 '@cid' => $cid,
                 '@val' => $values[$cid][$tok_key.'.'.$tmp_key],
                 '@url' => $url,
                 '@cur_token_raw' => $cur_token_raw
              ));
           } */

        }
     }
  }
}

function autologin_civi_tokens_createLoginUrl ($alu_service, $absurl, $drupalid, $path, $site_url) {
  $autoPath = $alu_service->create($drupalid, $path, false);
  if ( $absurl) {
     return $site_url . '/' . $autoPath;
  }
  else {
     return $autoPath;
  }
}

function autologin_civi_tokens_setToken($row, $profile, $token, $value) {
  $row->format('text/html');
  $row->tokens($profile, $token, $value);
  // $row->format('text/plain');
  // $row->tokens($profile, $token, $value);

}