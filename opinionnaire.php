<?php

/*
	Plugin Name: Opinionnaire plugin
	Description: A tool to import Opinionnaire&reg;-style Survey and generate Fast Forum&reg;-style reports and graphics
	Author: <a href="http://NationalDialogueNetwork.org/about-us" target="_new">National Dialogue Network</a>
	Version: 1.0
*/
	error_reporting(1);
	nocache_headers();
	global $opin_db_version;
	$opin_db_version = '1.00';
	$opin_version = '1.00';
	DEFINE('OPIN_MESSAGE_1','Please add credentials to access the reports.'); //message=1
	DEFINE('OPIN_SAMPLE_DATA_SNAPSHOT_DATE','2015-09-21'); //date of the sample data snapshot
	DEFINE('OPIN_VERSION',$opin_version); //app version

	register_activation_hook( __FILE__, 'opin_install' );
	register_activation_hook( __FILE__, 'opin_install_data' );

	add_action('admin_menu','opinionnaire_setup_menu');
	add_action('wp_ajax_get_custom_demos','custom_demo_db');

	function opinionnaire_setup_menu() {
		$api_array = array('SurveyGizmo');
		add_menu_page('Opinionnaire Setup','<span class="opin-title" title="Opinionnaire&reg; Survey analysis and Fast Forum&reg; reports">Opinionnaire&reg;</span>','manage_options','opinionnaire','opinionnaire_main_menu');
		add_submenu_page('opinionnaire','SurveyGizmo','SurveyGizmo','manage_options','survey-gizmo','opinionnaire_survey_gizmo_form_init');
		add_submenu_page('opinionnaire','Manage Survey Source Credentials','Survey Credentials','manage_options','manage-survey-source-credentials','opinionnaire_manage_source_credentials_form_init');
		add_submenu_page('opinionnaire','Sample Data','Sample Data','manage_options','sample-data','opinionnaire_sample_data_init');
	}

	function opinionnaire_main_menu() {
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
		require_once('welcome.php');
	}

	// function for interacting with the custom demographics table.
	// This function should either take "insert", "remove", "update" or "list" to perform the correct database action.
	function custom_demo_db($action = NULL, $survey_id = NULL, $collection = NULL) {
		global $wpdb;
		$table = $wpdb->prefix . 'opinionnaire_custom_demographics';
		if(isset($_REQUEST['insert'])) {
			$action = 'insert';
		} elseif(isset($_REQUEST['remove'])) {
			$action = 'remove';
		} elseif(isset($_REQUEST['update'])) {
			$action = 'update';
		}

		if($_REQUEST && $action == 'insert') {
			$data['survey_id'] = filter_var($_REQUEST['survey_id'], FILTER_SANITIZE_NUMBER_INT);
			foreach($_REQUEST['custom_demo_select'] as $value) {
				$data['serial_custom_demographic'] = serialize($value);
			}
			if($wpdb->insert($table,$data)) {
				$newJson = unserialize($data['serial_custom_demographic']);
				$newJson = json_encode($newJson);
				echo($newJson);
				echo "##|" . $wpdb->insert_id . "##|";
				return true;
			}
		} elseif($action == 'update') {
			$data['survey_id'] = filter_var($_REQUEST['survey_id'], FILTER_SANITIZE_NUMBER_INT);
			$data['db_id'] = '';
			foreach($_REQUEST['custom_demo_select'] as $value) {
				$data['db_id'] = filter_var($value['db_id'], FILTER_SANITIZE_NUMBER_INT);
				unset($value['db_id']);
				$data['serial_custom_demographic'] = serialize($value);
			}
			$passed = $wpdb->update($table,array('serial_custom_demographic' => $data['serial_custom_demographic']),array('id' => $data['db_id'],'survey_id' => $data['survey_id']),array('%s'));
			if($passed) {
				$newJson = unserialize($data['serial_custom_demographic']);
				$newJson = json_encode($newJson);
				echo($newJson);
				return true;
			} else {
				return false;
			}
		} elseif($action == 'list') {
			if(isset($_REQUEST['survey_id'])) {
				$report_id = filter_var($_REQUEST['survey_id'], FILTER_SANITIZE_NUMBER_INT);
			}
			$data = $wpdb->get_results("SELECT * FROM $table WHERE survey_id = $report_id ORDER BY created");
			return $data;
		} elseif(isset($_REQUEST['db_id']) && $action == 'remove') {
			$id = filter_var($_REQUEST['db_id'], FILTER_SANITIZE_NUMBER_INT);
			if($wpdb->delete($table,array('id' => $id))) {
				echo 1;
			}
		}
	}

	//sample data views
	function opinionnaire_sample_data_init() {
		$api_selected = 'Sample Data';
		$survey_id = "1581595";
		$significant_threshold = 0;
		$colorization_threshold = 0;
		$threshold_color_above = '#3485B5';
		$threshold_color_below = '#D64523';
		$value_statement_question_array = array();
		$demographic_question_array = array();
		$print_button = false;
		$survey_title = '';
		$date = date('j F Y');
		$user_survey_title = "Opinionnaire&reg; Survey Fast Forum&reg; Report &#8211; $date";
		$participatory_resistance = '';
		$report_0_cats = '';
		$transparency = '0.4';
		$recall = '';
		$value_statement_questions_selected_array = array();
		$demographic_questions_selected_array = array();
		$value_statement_questions_master = '';
		$demographic_questions_master = '';
		$custom_demographics_array = array();
		$custom_demographics_master = '';
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
		wp_enqueue_script('loading', $plugindir . '/js/loading.js');
		if (!isset($_REQUEST['reset'])) {
			if(isset($_REQUEST['significant_threshold'])) $significant_threshold = (int) sanitize_text_field($_REQUEST['significant_threshold']);
			if(isset($_REQUEST['colorization_threshold'])) $colorization_threshold = (int) sanitize_text_field($_REQUEST['colorization_threshold']);
			if(isset($_REQUEST['threshold_color_above'])) $threshold_color_above = sanitize_hex_color($_REQUEST['threshold_color_above']);
			if(isset($_REQUEST['threshold_color_below'])) $threshold_color_below = sanitize_hex_color($_REQUEST['threshold_color_below']);

			// min/max of threshold values is 1 and 100;
			if(!empty($significant_threshold)) $significant_threshold = min(100,max(0,$significant_threshold));
			if(!empty($colorization_threshold)) $colorization_threshold = min(100,max(0,$colorization_threshold));
			if(isset($_REQUEST['survey_title'])) $survey_title = sanitize_text_field($_REQUEST['survey_title']);
			if(isset($_REQUEST['user_survey_title'])) $user_survey_title = sanitize_text_field($_REQUEST['user_survey_title']);

			//build an array of the selected primary and secondary questions selected
			if(isset($_REQUEST['value_statement_questions']) && isset($_REQUEST['demographic_questions']) && !empty($_REQUEST['value_statement_questions']) && !empty($_REQUEST['demographic_questions'])) {

				$value_statement_question_array = explode(',', sanitize_text_field($_REQUEST['value_statement_questions']));
				$demographic_question_array = explode(',', sanitize_text_field($_REQUEST['demographic_questions']));
				$value_statement_questions_selected_array = $value_statement_question_array;
				$demographic_questions_selected_array = $demographic_question_array;
			} else {
				foreach($_REQUEST as $kR => $vR) {
					// strstr_after returns the end of the string that contains the second parameter
					//'primary_19' would return '19' which is the id of the question at SurveyGizmo
					$check_p = strstr_after($vR, 'valuestatements_', true);
					if($check_p) {
						$value_statement_question_array[] = $check_p;
					}
					$check_s = strstr_after($vR, 'demographics_', true);
					if($check_s) {
						$demographic_question_array[] = $check_s;
					}
					$check_cd = strstr_after($vR, 'custom_demo_select_', true);
					if($check_cd) {
						$custom_demographics_array[] = $check_cd;
					}
				}
			}
			if (isset($_REQUEST['participatory_resistance'])) $participatory_resistance = sanitize_text_field($_REQUEST['participatory_resistance']);
			if (isset($_REQUEST['report_0_cats'])) $report_0_cats = sanitize_text_field($_REQUEST['report_0_cats']);
			if (isset($_REQUEST['transparency'])) $transparency = sanitize_text_field($_REQUEST['transparency']);

			//jscolor returns the colors without the preceding #, so I added this to unify the data regardless of where it came from
			if (!empty($threshold_color_above)) {
				if($threshold_color_above[0] !== '#') $threshold_color_above = '#' . $threshold_color_above;
			}
			if(!empty($threshold_color_below)) {
				if($threshold_color_below[0] !== '#') $threshold_color_below = '#' . $threshold_color_below;
			}

			if(!empty($_REQUEST['valuestatements_master'])) $value_statement_questions_master = sanitize_text_field($_REQUEST['valuestatements_master']);
			if(!empty($_REQUEST['demographics_master'])) $demographic_questions_master = sanitize_text_field($_REQUEST['demographics_master']);
			if(!empty($_REQUEST['custom_demographics_master'])) {
				$custom_demographics_master = sanitize_text_field($_REQUEST['custom_demographics_master']);
			}
			if(!empty($custom_demographics_array)) {
				$custom_demographics_master = serialize($custom_demographics_array);
			}
		}
		echo '<div id="retrieve_survey_form">' . PHP_EOL;
		echo '<h1>Opinionnaire&reg; Survey analysis and Fast Forums&reg; reports</h1>' . PHP_EOL;
		$submit = null;
		if(isset($_REQUEST['submit'])) $submit = sanitize_text_field($_REQUEST['submit']);
		if(isset($_REQUEST['submit_top'])) {
			$submit = sanitize_text_field($_REQUEST['submit_top']);
			$_REQUEST['submit'] = $submit;
		}
		$override_class = '';
		$override_css = '';
		if($submit == null || $submit == 'Start Over' || $submit == 'Back to Choose Survey') {
			$override_class = ' select_survey';
			$override_css = ' style="display:none"';
			$submit = choose_survey($api_selected,$survey_id,false);
		} else {
			switch($_REQUEST['submit']) {
				case 'Retrieve Survey'://fall through
				case 'Back to Retrieve Survey':
					if(empty($survey_id)) {
						$override_class = ' select_survey';
						$override_css = ' style="display:none"';
						echo '<div style="color:red">You must select a survey.<br />' . PHP_EOL;
						$submit = choose_survey($api_selected,$survey_id,false);
					} else {
						$submit = choose_survey_questions(
							$api_selected,
							$survey_id,
							$survey_title,
							$value_statement_question_array,
							$demographic_question_array,
							$user_survey_title,
							$transparency,
							$threshold_color_above,
							$threshold_color_below,
							$significant_threshold,
							$colorization_threshold,
							$participatory_resistance,
							$report_0_cats,
							$value_statement_questions_selected_array,
							$demographic_questions_selected_array,
							$value_statement_questions_master,
							$demographic_questions_master,
							$custom_demographics_array,
							$custom_demographics_master,
							false
						);
						$recall = 'Back to Choose Survey';
					}
					break;
				case 'Generate Report':
					$recall = 'Back to Retrieve Survey';
					if(empty($value_statement_question_array) || (empty($demographic_question_array) && empty($custom_demographics_master))) {
						$message = '<div style="color:red">' . PHP_EOL;
						//if(empty($threshold_number) && empty($threshold_color)) $message .= 'You must select both a threshold number and a threshold color or neither.<br />' . PHP_EOL;
						if(empty($value_statement_question_array)) $message .= 'You must select at least one value statement question.<br />' . PHP_EOL;
						if(empty($demographic_question_array)) $message .= 'You must select at least one demographic question.<br />' . PHP_EOL;
						$message .= '</div>' . PHP_EOL;
						echo $message;
						$submit = choose_survey_questions(
							$api_selected,
							$survey_id,
							$survey_title,
							$value_statement_question_array,
							$demographic_question_array,
							$user_survey_title,
							$transparency,
							$threshold_color_above,
							$threshold_color_below,
							$significant_threshold,
							$colorization_threshold,
							$participatory_resistance,
							$report_0_cats,
							$value_statement_questions_selected_array,
							$demographic_questions_selected_array,
							$value_statement_questions_master,
							$demographic_questions_master,
							$custom_demographics_array,
							$custom_demographics_master,
							false
						);
						$_REQUEST['submit'] = 'Retrieve Survey';
					} else {
						$value_statement_questions = implode(',',$value_statement_question_array);
						$demographic_questions = implode(',',$demographic_question_array);
						echo '<h2>Download or Print Your Report</h2>' . PHP_EOL;
						echo '<form name="download_print" method="post">' . PHP_EOL;
						echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
						echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
						echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
						echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
						echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
						echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
						echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
						echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
						echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
						echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
						echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
						echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
						echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
						echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
						echo '<h3>API: ' . $api_selected . '</h3>' . PHP_EOL;
						echo '<h3>Survey Title: ' . $survey_title . '</h3>' . PHP_EOL;
						echo '<h3>Category Significance Threshold: ' . $significant_threshold . '</h3>' . PHP_EOL;
						echo '<h3>Cell Colorization Threshold: ' . $colorization_threshold . '</h3>' . PHP_EOL;
						echo '<h3>Threshold Color Above: <span class="preview-color" style="background-color:' . $threshold_color_above . '">' . $threshold_color_above . '</span></h3>' . PHP_EOL;
						echo '<h3>Threshold Color Below: <span class="preview-color" style="background-color:' . $threshold_color_below . '">' . $threshold_color_below . '</span></h3>' . PHP_EOL;
						echo '<div>' . PHP_EOL;
						echo '<h3><div class="no-print"><label for="transparency">Threshold Color Transparency: <output id="t_output">' . $transparency . '</output></div></h3>' . PHP_EOL;
						echo '<div class="no-print">';
						echo '0.1';
						echo '<input type="button" id="transparency_decrease" name="transparency_decrease" class="transparency-modify" value="-" />';
						echo '<input id="transparency" name="transparency" type="range" value="' . $transparency . '" step="0.1" min="0.1" max="1.0" />';
						echo '<input type="button" id="transparency_increase" name="transparency_increase" class="transparency-modify" value="+" />';
						echo '1.0';
						echo '</div>' . PHP_EOL;
						echo '<div id="transparency_error" class="no-print"></div>' . PHP_EOL;
						echo '</div>' . PHP_EOL;
						$pr_show = 'Do Not Display';
						if(!empty($participatory_resistance)) $pr_show = 'Display';
						echo '<h3>Participatory Resistance: ' . $pr_show . '</h3>' . PHP_EOL;
						$r0c = 'Do Not Suppress';
						if(!empty($report_0_cats)) $r0c = 'Suppress';
						echo '<h3>Supress Categories with 0 Responses: ' . $r0c . '</h3>' . PHP_EOL;
						echo '<h3>Preview:</h3>' . PHP_EOL;
						if(!isset($custom_demographics)) {
							$custom_demographics = null;
						}
						$created = generate_report(
							$api_selected,
							$survey_id,
							$survey_title,
							$value_statement_question_array,
							$demographic_question_array,
							$user_survey_title,$transparency,
							$threshold_color_above,
							$threshold_color_below,
							$significant_threshold,
							$colorization_threshold,
							$participatory_resistance,
							$report_0_cats,
							$value_statement_questions_master,
							$demographic_questions_master,
							$custom_demographics_array,
							$custom_demographics_master,
							false,
							$custom_demographics
						);
						if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
							$submit = 'Start Over';
						} else {
							$submit = 'Download';
							$print_button = true;
						}
						echo '<p class="no-print">' . PHP_EOL;
						submit_button($submit,'primary','submit_top',false);
						echo '&nbsp;&nbsp;';
						if($recall) {
							submit_button($recall,'secondary','submit_top',false);
							echo '&nbsp;&nbsp;';
						}
						if($print_button) {
							//submit_button('Print','secondary','print_top',false);
							echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
							echo '&nbsp;&nbsp;';
						}
						submit_button('Reset Form','secondary','reset_top',false);
						if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
							echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
						}
						echo '<hr />' . PHP_EOL;
						echo '</p>' . PHP_EOL;
						echo $created;
					}
					break;
				case 'Download':
					$recall = 'Back to Retrieve Survey';
					echo '<h3>Preview:</h3>' . PHP_EOL;
					if(!isset($custom_demographics)) {
						$custom_demographics = null;
					}
					$created = generate_report(
						$api_selected,
						$survey_id,
						$survey_title,
						$value_statement_question_array,
						$demographic_question_array,
						$user_survey_title,$transparency,
						$threshold_color_above,
						$threshold_color_below,
						$significant_threshold,
						$colorization_threshold,
						$participatory_resistance,
						$report_0_cats,
						$value_statement_questions_master,
						$demographic_questions_master,
						$custom_demographics_array,
						$custom_demographics_master,
						false,
						$custom_demographics
					);
					if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
						$submit = 'Start Over';
					} else {
						$submit = 'Download';
						$print_button = true;
					}
					echo '<form name="download_print" method="post">' . PHP_EOL;
					echo '<p class="no-print">' . PHP_EOL;
					submit_button($submit,'primary','submit_top',false);
					echo '&nbsp;&nbsp;';
					if($recall) {
						submit_button($recall,'secondary','submit_top',false);
						echo '&nbsp;&nbsp;';
					}
					if($print_button) {
						//submit_button('Print','secondary','print_top',false);
						echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
						echo '&nbsp;&nbsp;';
					}
					submit_button('Reset Form','secondary','reset_top',false);
					if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
						echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
					}

					if(!isset($value_statement_questions)){
						$value_statement_questions = '';
					}
					if(!isset($demographic_questions)){
						$demographic_questions = '';
					}
					
					echo '<hr />' . PHP_EOL;
					echo '</p>' . PHP_EOL;
					echo $created;
					echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
					echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
					echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
					echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
					echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
					echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
					echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
					echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
					echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
					echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
					echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
					echo '<input type="hidden" id="transparency" name="transparency" value="' . $transparency . '" />' . PHP_EOL;
					echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
					echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
					echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
					echo '<span style="display:none"><output id="t_output">' . $transparency . '</output></span>' . PHP_EOL;
					break;
				case 'Print':
					$recall = 'Back to Retrieve Survey';
					echo '<h3>Preview:</h3>' . PHP_EOL;
					if(!isset($custom_demographics)) {
						$custom_demographics = null;
					}
					$created = generate_report(
						$api_selected,
						$survey_id,
						$survey_title,
						$value_statement_question_array,
						$demographic_question_array,
						$user_survey_title,$transparency,
						$threshold_color_above,
						$threshold_color_below,
						$significant_threshold,
						$colorization_threshold,
						$participatory_resistance,
						$report_0_cats,
						$value_statement_questions_master,
						$demographic_questions_master,
						$custom_demographics_array,
						$custom_demographics_master,
						false,
						$custom_demographics
					);
					if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
						$submit = 'Start Over';
					} else {
						$submit = 'Download';
						$print_button = true;
					}
					echo '<form name="download_print" method="post">' . PHP_EOL;
					echo '<p class="no-print">' . PHP_EOL;
					submit_button($submit,'primary','submit_top',false);
					echo '&nbsp;&nbsp;';
					if($recall) {
						submit_button($recall,'secondary','submit_top',false);
						echo '&nbsp;&nbsp;';
					}
					if($print_button) {
						//submit_button('Print','secondary','print_top',false);
						echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
						echo '&nbsp;&nbsp;';
					}
					submit_button('Reset Form','secondary','reset_top',false);
					if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
						echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
					}
					if(!isset($value_statement_questions)){
						$value_statement_questions = '';
					
					}
					if(!isset($demographic_questions)){
						$demographic_questions = '';
					}
					echo '<hr />' . PHP_EOL;
					echo '</p>' . PHP_EOL;
					echo $created;
					echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
					echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
					echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
					echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
					echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
					echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
					echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
					echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
					echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
					echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
					echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
					echo '<input type="hidden" id="transparency" name="transparency" value="' . $transparency . '" />' . PHP_EOL;
					echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
					echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
					echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
					echo '<span style="display:none"><output id="t_output">' . $transparency . '</output></span>' . PHP_EOL;
					break;
			}
		}
		echo '<div class="no-print' . $override_class . '"' . $override_css . '>' . PHP_EOL;
		echo '<hr />' . PHP_EOL;
		submit_button($submit,'primary','submit',false);
		echo '&nbsp;&nbsp;';
		if($recall) {
			submit_button($recall,'secondary','submit',false);
			echo '&nbsp;&nbsp;';
		}
		if($print_button) {
			//submit_button('Print','secondary','print',false);
			echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
			echo '&nbsp;&nbsp;';
		}
		submit_button('Reset Form','secondary','reset',false);
		echo '</div>' . PHP_EOL;
		echo '</form>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
	}

	function send_message($id, $message, $progress) {
		$d = array('message' => $message , 'progress' => $progress);

		echo "id: $id" . PHP_EOL;
		echo "data: " . json_encode($d) . PHP_EOL;
		echo PHP_EOL;

		ob_flush();
		flush();
	}

	//credentials forms views
	function opinionnaire_manage_source_credentials_form_init() {
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
		wp_enqueue_style('opinionnaire-credentials-css', $plugindir . '/css/credentials.css');
		global $wpdb;
		$users_table_name = $wpdb->prefix . 'opinionnaire_users';
		$user_id = get_current_user_id();
		$errors = array();
		$credential_id = (isset($_REQUEST['credential_id']) ? sanitize_text_field($_REQUEST['credential_id']) : false);
		if(empty($credential_id)) $credential_id = (isset($_REQUEST['ex_credential_id']) ? sanitize_text_field($_REQUEST['ex_credential_id']) : '');
		$source_id = '';
		$source_user = '';
		$source_pass = '';
		remove_query_arg('page');
		$test = array();//array to hold the results of a credentials test
		if(isset($_REQUEST['submit'])) {
			if($_REQUEST['submit'] == 'Add Credentials' || $_REQUEST['submit'] == 'Save Credentials') {
				//validate form submission data
				if(!isset($_REQUEST['source_id']) || empty($_REQUEST['source_id'])) {
					$errors['source_id'] = 'Please Select a Survey Source.';
				}
				if(empty($errors)) {
					$source_table_name = $wpdb->prefix . 'opinionnaire_sources';
					$source_id_sanitized = sanitize_text_field($_REQUEST['source_id']);
					$source_exists_check = $wpdb->get_results( "SELECT * FROM $source_table_name WHERE id={$source_id_sanitized} LIMIT 1;" );
					if(!$source_exists_check) $errors['source_id'] = 'Please Select a Survey Source from the given list.';
				}
				if(!isset($_REQUEST['source_user']) || empty($_REQUEST['source_user'])) $errors['source_user'] = 'Please enter a username for the survey source.';
				if(!isset($_REQUEST['source_pass']) || empty($_REQUEST['source_pass'])) $errors['source_pass'] = 'Please enter a password for the survey source.';
				//check for duplicate entries
				if($_REQUEST['submit'] == 'Add Credentials') {
					$source_id_sanitized = sanitize_text_field($_REQUEST['source_id']);
					$source_user_sanitized = sanitize_text_field($_REQUEST['source_user']);
					// $dup_check = $wpdb->get_results( "SELECT * FROM $users_table_name WHERE user_id=$user_id AND source_id={$source_id_sanitized}");
					$dup_check = $wpdb->get_results( "SELECT * FROM $users_table_name WHERE user_id=$user_id"); // source_id no longer used by wordpress_db

					if(empty($errors)) {
						if($dup_check) {
							//set the error message
							$errors['general'] = 'This user and source combination already has stored credentials. If you would like to edit those credentials, please click the username in the table above.';
						} else {
							//encrypt the password
							$source_pass_sanitized = sanitize_text_field($_REQUEST['source_pass']);
							$encrypted = opin_encrypt_decrypt('encrypt',$source_pass_sanitized,false,false);
							//insert the user into the database
							$created = date('Y-m-d H:i:s',time());
							$inserted = $wpdb->insert(
								$users_table_name,
								array(
									'user_id' => $user_id,
									'source_id' => $source_id_sanitized,
									'source_user' => $source_user_sanitized,
									'source_pass' => $encrypted['value'],
									'source_key' => $encrypted['key'],
									'source_iv' => $encrypted['iv'],
									'created' => $created
								)
							);
						}
					}
				}
				if($_REQUEST['submit'] == 'Save Credentials') {
					if(!isset($_REQUEST['ex_credential_id'])) {
						$errors['general'] = 'Credentials could not be found.';
					}
					if(empty($errors)) {
						$credential_id = sanitize_text_field($_REQUEST['ex_credential_id']);
						$ex_credentials_check = $wpdb->get_results( "SELECT * FROM $users_table_name WHERE id=$credential_id AND user_id=$user_id LIMIT 1;" );
						if(empty($ex_credentials_check)) $errors['general'] = 'Unable to locate user credentials. Please, try again.';
					}
					if(empty($errors)) {
						$source_id_sanitized = sanitize_text_field($_REQUEST['source_id']);
						$source_user_sanitized = sanitize_text_field($_REQUEST['source_user']);
						//encrypt the password
						$source_pass_sanitized = sanitize_text_field($_REQUEST['source_pass']);
						$encrypted = opin_encrypt_decrypt('encrypt',$source_pass_sanitized,false,false);
						$updated = $wpdb->update(
							$users_table_name,
							array(
								'user_id' => $user_id,
									'source_id' => $source_id_sanitized,
									'source_user' => $source_user_sanitized,
								'source_pass' => $encrypted['value'],
								'source_key' => $encrypted['key'],
								'source_iv' => $encrypted['iv']
							),
							array('id'=>$credential_id),
							array(
								'%d',
								'%d',
								'%s',
								'%s',
								'%s',
								'%s'
							),
							array('%d')
						);
						if($updated) {
							$credential_id = '';
						} else {
							$errors['general'] = 'The credentials were not saved.';
						}
					}
				}
			}
			if($_REQUEST['submit'] == 'Test') {
				//survey_test_api_call
				if(!isset($_REQUEST['credential_id'])) {
					$test_message[0] = 'Credentials could not be found.';
				}
				if(empty($test_message)) {
					$credential_id = sanitize_text_field($_REQUEST['credential_id']);
					$ex_credentials_check = $wpdb->get_results( "SELECT * FROM $users_table_name WHERE id=$credential_id AND user_id=$user_id LIMIT 1;" );
					if(!empty($ex_credentials_check)) {
						if(survey_test_api_call($ex_credentials_check)) {
							$test_message[$_REQUEST['credential_id']] = '<p class="green">Tested Successfully</p>';
						} else {
							$test_message[$_REQUEST['credential_id']] = '<p class="error">Test Unsuccessful, please try again.</p>';
						}
					} else {
						$test_message[0] = 'Credentials could not be found.';
					}
				}
			}
			if(!empty($credential_id)) {
				$retrieve_source_user = $wpdb->get_results( "SELECT * FROM $users_table_name WHERE id=$credential_id AND user_id=$user_id LIMIT 1;" );
				if(!empty($retrieve_source_user)) {
					$source_id = (isset($_REQUEST['source_id']) ? sanitize_text_field($_REQUEST['source_id']) : $retrieve_source_user[0]->source_id);
					$source_user = (isset($_REQUEST['source_user']) ? sanitize_text_field($_REQUEST['source_user']) : $retrieve_source_user[0]->source_user);
					if(isset($_REQUEST['source_pass'])) {
						$source_pass = sanitize_text_field($_REQUEST['source_pass']);
					} else {
						if(isset($retrieve_source_user[0]->source_pass) && isset($retrieve_source_user[0]->source_key) && isset($retrieve_source_user[0]->source_iv)) {
							$source_pass = opin_encrypt_decrypt('decrypt', $retrieve_source_user[0]->source_pass, $retrieve_source_user[0]->source_key, $retrieve_source_user[0]->source_iv);
							$source_pass = rtrim($source_pass);
						}
					}
				} else {
					$errors['general'] = 'Unable to locate user credentials. Please, try again.';
				}
			}
		}
		$user_credentials = $wpdb->get_results( "SELECT a.id,a.source_user,b.name FROM {$wpdb->prefix}opinionnaire_users a LEFT JOIN {$wpdb->prefix}opinionnaire_sources b ON b.id=a.source_id WHERE a.user_id = $user_id;");
		$sources = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}opinionnaire_sources;" );
		if($inserted || $updated) {
			echo '<div class="positive_message">' . PHP_EOL;
			if ($inserted) echo 'Credentials Successfully Inserted!';
			if ($updated) echo 'Credentials Successfully Updated!';
			echo '</div>' . PHP_EOL;
		}
		if(!empty($_REQUEST['message'])) {
			echo '<div class="error_message">' . PHP_EOL;
			switch($_REQUEST['message']) {
				case 1:
					echo OPIN_MESSAGE_1;
					break;
			}
			echo '</div>' . PHP_EOL;
		}
		if(isset($test_message[0])) {
			echo '<div class="error_message">' . PHP_EOL;
			echo $test_message[0];
			echo '</div>' . PHP_EOL;
		}
		echo '<table class="users">' . PHP_EOL;
		echo '<thead>' . PHP_EOL;
		echo '<tr><th>API Token</th><th>Survey Source</th><th>Test</th><th>Test Result</th></tr>' . PHP_EOL;
		echo '</thead>' . PHP_EOL;
		echo '<tbody>' . PHP_EOL;
		if(!empty($user_credentials)) {
			foreach($user_credentials as $user) {
				echo '<tr><td>' . PHP_EOL;
				echo '<form name="edit_credentials' . $user->id . '" method="post">' . PHP_EOL;
				echo '<input type="hidden" id="credential_id" name="credential_id" value="' . $user->id . '" />' . PHP_EOL;
				echo '<input type="submit" id="submit' . $user->id . '" name="submit" value="' . $user->source_user . '" />' . PHP_EOL;
				echo '</form>' . PHP_EOL;
				echo '</td><td>' . $user->name . '</td>' . PHP_EOL;
				echo '<td>' . PHP_EOL;
				echo '<form anme="test_credentials' . $user->id . '" method="post">' . PHP_EOL;
				echo '<input type="hidden" id="credential_id" name="credential_id" value="' . $user->id . '" />' . PHP_EOL;
				echo '<input type="submit" id="submit' . $user->id . '" name="submit" value="Test" />' . PHP_EOL;
				echo '</td>' . PHP_EOL;
				echo '<td class="test-result-message">' . (isset($test_message[$user->id]) ? $test_message[$user->id] : '') . '</td>' . PHP_EOL;
				echo '</tr>' . PHP_EOL;
			}
		} else {
			echo '<tr><td colspan=2>No credentials found.</td></tr>' . PHP_EOL;
		}
		echo '</tbody>' . PHP_EOL;
		echo '</table>' . PHP_EOL;
		if(!empty($credential_id)) {
			echo '<br /><br /><a href="/wp-admin/admin.php?page=manage-survey-source-credentials" class="user-add-button">Add Credentials</a><br /><br />' . PHP_EOL;
			echo '<h2>Edit Credentials</h2>' . PHP_EOL;
			echo '<form name="edit_credentials" method="post">' . PHP_EOL;
			echo '<input type="hidden" id="ex_credential_id" name="ex_credential_id" value="' . $credential_id . '" />' . PHP_EOL;
			$button_text = 'Save Credentials';
		} else {
			echo '<h2>Add Credentials</h2>' . PHP_EOL;
			echo '<form name="add_credentials" method="post">' . PHP_EOL;
			$button_text = 'Add Credentials';
		}
		echo '<div id="add_credentials_form">' . PHP_EOL;
		echo '<div class="notes">' . PHP_EOL;
		if(!empty($errors)) {
			echo '<div class="error">Please correct the errors below:' . PHP_EOL;
			if(isset($errors['general'])) echo '<br />' . $errors['general'] . PHP_EOL;
			echo '</div>' . PHP_EOL;
		}
		echo '</div>' . PHP_EOL;
		echo '<div class="input">' . PHP_EOL;
		echo '<label for="sorce_id">Survey Source: </label>' . PHP_EOL;
		echo '<select id="source_id" name="source_id"> ' . PHP_EOL;
		echo '<option value="">Select Survey Source...</option>' . PHP_EOL;
		foreach($sources as $source) {
			echo '<option value="' . $source->id . '"';
			if($source->id == $source_id) echo ' selected="selected"';
			echo '>' . $source->name . '</option>' . PHP_EOL;
		}
		echo '</select>' . PHP_EOL;
		if(isset($errors['source_id'])) echo '<div class="error">' . $errors['source_id'] . '</div>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
		echo '<div class="input">' . PHP_EOL;
		echo '<label for="source_user">API Token: </label>' . PHP_EOL;
		echo '<input type="text" id="source_user" name="source_user" value="' . $source_user . '" />' . PHP_EOL;
		if(isset($errors['source_user'])) echo '<div class="error">' . $errors['source_user'] . '</div>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
		echo '<div class="input">' . PHP_EOL;
		echo '<label for="source_pass">API Token Secret: </label>' . PHP_EOL;
		echo '<input type="password" id="source_pass" name="source_pass" value="' . $source_pass . '" />' . PHP_EOL;
		if(isset($errors['source_pass'])) echo '<div class="error">' . $errors['source_pass'] . '</div>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
		echo '<input type="submit" id="submit" name="submit" value="' . $button_text . '" />' . PHP_EOL;
		echo '</div>' . PHP_EOL;
	}

	function opinionnaire_survey_gizmo_form_init() {
		global $wpdb;
		$api_selected = 'SurveyGizmo';
		$user_id = get_current_user_id();
		$survey_id = '';
		$recall = '';
		//make sure there is a source entry for the survey api chosen
		$opin_source = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}opinionnaire_sources WHERE name='$api_selected' LIMIT 1;" );
		$check_survey_exists = opin_redirect_to_add_credentials($opin_source,$api_selected);
		$plugindir = plugin_dir_url(__FILE__);
		wp_enqueue_script(plugins_url( 'loading.js', __FILE__ ));
		if($check_survey_exists) {
			echo $check_survey_exists;
		} else {
			//make sure the user has credentials for the source
			$survey_source_id = $opin_source[0]->id;
			$opin_credentials = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}opinionnaire_users WHERE user_id=$user_id AND source_id=$survey_source_id LIMIT 1;" );
			$check_credentials_exist = opin_check_credentials($opin_credentials,$api_selected);
			if($check_credentials_exist) {
				echo $check_credentials_exist;
			} else {
				$significant_threshold = 0;
				$colorization_threshold = 0;
				$threshold_color_above = '#3485B5';
				$threshold_color_below = '#D64523';
				$value_statement_question_array = array();
				$demographic_question_array = array();
				$submit = 'Retrieve Survey';
				$print_button = false;
				$survey_title = '';
				$date = date('j F Y');
				$user_survey_title = "Opinionnaire&reg; Survey Fast Forum&reg; Report &#8211; $date";
				$participatory_resistance = '';
				$report_0_cats = '';
				$transparency = '0.4';
				$value_statement_questions_selected_array = array();
				$demographic_questions_selected_array = array();
				$custom_demographics_array = array();
				$value_statement_questions_master = '';
				$demographic_questions_master = '';
				$custom_demographics_master = '';
				if(!isset($_REQUEST['reset'])) {
					if(isset($_REQUEST['survey_id'])) $survey_id = filter_var($_REQUEST['survey_id'], FILTER_SANITIZE_NUMBER_INT);
					if(isset($_REQUEST['significant_threshold'])) $significant_threshold = sanitize_text_field($_REQUEST['significant_threshold']);
					if(isset($_REQUEST['colorization_threshold'])) $colorization_threshold = sanitize_text_field($_REQUEST['colorization_threshold']);
					if(isset($_REQUEST['threshold_color_above'])) $threshold_color_above = sanitize_text_field($_REQUEST['threshold_color_above']);
					if(isset($_REQUEST['threshold_color_below'])) $threshold_color_below = sanitize_text_field($_REQUEST['threshold_color_below']);
					//min/max of threshold values is 1 and 100;
					if(!empty($significant_threshold)) $significant_threshold = min(100,max(0,$significant_threshold));
					if(!empty($colorization_threshold)) $colorization_threshold = min(100,max(0,$colorization_threshold));
					if(isset($_REQUEST['survey_title'])) $survey_title = $_REQUEST['survey_title'];
					if(isset($_REQUEST['user_survey_title'])) $user_survey_title = $_REQUEST['user_survey_title'];
					//build an array of the selected primary and secondary questions selected
					if(isset($_REQUEST['value_statement_questions']) && isset($_REQUEST['demographic_questions']) && !empty($_REQUEST['value_statement_questions']) && !empty($_REQUEST['demographic_questions'])) {
						$value_statement_question_array = explode(',', sanitize_text_field($_REQUEST['value_statement_questions']) );
						$demographic_question_array = explode(',', sanitize_text_field($_REQUEST['demographic_questions']) );
						$value_statement_questions_selected_array = $value_statement_question_array;
						$demographic_questions_selected_array = $demographic_question_array;
						$custom_demographics_master = sanitize_text_field($_REQUEST['custom_demographics_master']);
					} else {
						foreach($_REQUEST as $kR => $vR) {
							//strstr_after returns the end of the string that contains the second parameter
							//'primary_19' would return '19' which is the id of the question at SurveyGizmo
							$check_p = strstr_after($vR,'valuestatements_',true);
							if($check_p) {
								$value_statement_question_array[] = $check_p;
							}
							$check_s = strstr_after($vR,'demographics_',true);
							if($check_s) {
								$demographic_question_array[] = $check_s;
							}
						}
						$custom_demographics = sanitize_text_field($_REQUEST['custom_demo_select']);

					}
					if(isset($_REQUEST['participatory_resistance'])) $participatory_resistance = sanitize_text_field($_REQUEST['participatory_resistance']);
					if(isset($_REQUEST['report_0_cats'])) $report_0_cats = sanitize_text_field($_REQUEST['report_0_cats']);
					if(isset($_REQUEST['transparency'])) $transparency = sanitize_text_field($_REQUEST['transparency']);

					//jscolor returns the colors without the preceding #, so I added this to unify the data regardless of where it came from
					if(!empty($threshold_color_above)) {
						if($threshold_color_above[0] !== '#') $threshold_color_above = '#' . $threshold_color_above;
					}
					if(!empty($threshold_color_below)) {
						if($threshold_color_below[0] !== '#') $threshold_color_below = '#' . $threshold_color_below;
					}
					if(!empty($_REQUEST['valuestatements_master'])) $value_statement_questions_master = sanitize_text_field($_REQUEST['valuestatements_master']);
					if(!empty($_REQUEST['demographics_master'])) $demographic_questions_master = sanitize_text_field($_REQUEST['demographics_master']);
					if(!empty($_REQUEST['custom_demographics_master'])) {
						$custom_demographics_master = sanitize_text_field($_REQUEST['custom_demographics_master']);
					}
					if(!empty($custom_demographics_array)) {
						$custom_demographics_master = serialize($custom_demographics_array);
					}
				}
				echo '<div id="retrieve_survey_form">' . PHP_EOL;
				echo '<h1>Opinionnaire&reg; Survey analysis and Fast Forums&reg; reports</h1>' . PHP_EOL;
				$submit = null;
				if(isset($_REQUEST['submit'])) $submit = sanitize_text_field($_REQUEST['submit']);
				if(isset($_REQUEST['submit_top'])) {
					$submit = sanitize_text_field($_REQUEST['submit_top']);
					$_REQUEST['submit'] = $submit;
				}
				$override_class = '';
				$override_css = '';
				if($submit == null || $submit == 'Start Over' || $submit == 'Back to Choose Survey') {
					$override_class = ' select_survey';
					$override_css = ' style="display:none"';
					$submit = choose_survey($api_selected,$survey_id,$opin_credentials);
				} else {
					switch($_REQUEST['submit']) {
						case 'Retrieve Survey'://fall through
						case 'Back to Retrieve Survey':
							if(empty($survey_id)) {
								$override_class = ' select_survey';
								$override_css = ' style="display:none"';
								echo '<div style="color:red">You must select a survey.<br />' . PHP_EOL;
								$submit = choose_survey($api_selected,$survey_id,$opin_credentials);
							} else {
								$submit = choose_survey_questions(
									$api_selected,
									$survey_id,
									$survey_title,
									$value_statement_question_array,
									$demographic_question_array,
									$user_survey_title,
									$transparency,
									$threshold_color_above,
									$threshold_color_below,
									$significant_threshold,
									$colorization_threshold,
									$participatory_resistance,
									$report_0_cats,
									$value_statement_questions_selected_array,
									$demographic_questions_selected_array,
									$value_statement_questions_master,
									$demographic_questions_master,
									$custom_demographics_array,
									$custom_demographics_master,
									$opin_credentials
								);
								$recall = 'Back to Choose Survey';
							}
							break;
						case 'Generate Report':
							$recall = 'Back to Retrieve Survey';
							if(empty($value_statement_question_array) || (empty($demographic_question_array) && empty($_REQUEST['custom_demo_select']))) {
								$message = '<div style="color:red">' . PHP_EOL;
								//if(empty($threshold_number) && empty($threshold_color)) $message .= 'You must select both a threshold number and a threshold color or neither.<br />' . PHP_EOL;
								if(empty($value_statement_question_array)) $message .= 'You must select at least one value statement question.<br />' . PHP_EOL;
								if(empty($demographic_question_array)) $message .= 'You must select at least one demographic question.<br />' . PHP_EOL;
								$message .= '</div>' . PHP_EOL;
								echo $message;
								$submit = choose_survey_questions(
									$api_selected,
									$survey_id,
									$survey_title,
									$value_statement_question_array,
									$demographic_question_array,
									$user_survey_title,
									$transparency,
									$threshold_color_above,
									$threshold_color_below,
									$significant_threshold,
									$colorization_threshold,
									$participatory_resistance,
									$report_0_cats,
									$value_statement_questions_selected_array,
									$demographic_questions_selected_array,
									$value_statement_questions_master,
									$demographic_questions_master,
									$custom_demographics_array,
									$custom_demographics_master,
									$opin_credentials
								);
								$_REQUEST['submit'] = 'Retrieve Survey';
							} else {
								$value_statement_questions = implode(',',$value_statement_question_array);
								$demographic_questions = implode(',',$demographic_question_array);
								echo '<h2>Download or Print Your Report</h2>' . PHP_EOL;
								echo '<form name="download_print" method="post" action="' . esc_url_raw($_SERVER['REQUEST_URI']) . '">' . PHP_EOL;
								echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
								echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
								echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
								echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
								echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
								echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
								echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
								echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
								echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
								echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
								echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
								echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
								echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
								echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
								echo '<h3>API: ' . $api_selected . '</h3>' . PHP_EOL;
								echo '<h3>Survey Title: ' . $survey_title . '</h3>' . PHP_EOL;
								echo '<h3>Category Significance Threshold: ' . $significant_threshold . '</h3>' . PHP_EOL;
								echo '<h3>Cell Colorization Threshold: ' . $colorization_threshold . '</h3>' . PHP_EOL;
								echo '<h3>Threshold Color Above: <span class="preview-color" style="background-color:' . $threshold_color_above . '">' . $threshold_color_above . '</span></h3>' . PHP_EOL;
								echo '<h3>Threshold Color Below: <span class="preview-color" style="background-color:' . $threshold_color_below . '">' . $threshold_color_below . '</span></h3>' . PHP_EOL;
								echo '<div>' . PHP_EOL;
								echo '<h3><div class="no-print"><label for="transparency">Threshold Color Transparency: <output id="t_output">' . $transparency . '</output></div></h3>' . PHP_EOL;
								echo '<div class="no-print">';
								echo '0.1';
								echo '<input type="button" id="transparency_decrease" name="transparency_decrease" class="transparency-modify" value="-" />';
								echo '<input id="transparency" name="transparency" type="range" value="' . $transparency . '" step="0.1" min="0.1" max="1.0" />';
								echo '<input type="button" id="transparency_increase" name="transparency_increase" class="transparency-modify" value="+" />';
								echo '1.0';
								echo '</div>' . PHP_EOL;
								echo '<div id="transparency_error" class="no-print"></div>' . PHP_EOL;
								echo '</div>' . PHP_EOL;
								$pr_show = 'Do Not Display';
								if(!empty($participatory_resistance)) $pr_show = 'Display';
								echo '<h3>Participatory Resistance: ' . $pr_show . '</h3>' . PHP_EOL;
								$r0c = 'Do Not Suppress';
								if(!empty($report_0_cats)) $r0c = 'Suppress';
								echo '<h3>Supress Categories with 0 Responses: ' . $r0c . '</h3>' . PHP_EOL;
								//echo '<h3>Value Statement Question(s): ' . $value_statement_questions . '</h3>' . PHP_EOL;
								//echo '<h3>Demographic Question(s): ' . $demographic_questions . '</h3>' . PHP_EOL;
								echo '<h3>Preview:</h3>' . PHP_EOL;
								echo '<image src="' . $plugindir . '/assets/loading.gif" id="loading_gif" name="loading_gif" alt="loading..." />' . PHP_EOL;
								$created = generate_report(
									$api_selected,
									$survey_id,
									$survey_title,
									$value_statement_question_array,
									$demographic_question_array,
									$user_survey_title,
									$transparency,
									$threshold_color_above,
									$threshold_color_below,
									$significant_threshold,
									$colorization_threshold,
									$participatory_resistance,
									$report_0_cats,
									$value_statement_questions_master,
									$demographic_questions_master,
									$custom_demographics_array,
									$custom_demographics_master,
									$opin_credentials,
									$custom_demographics
								);
								if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
									$submit = 'Start Over';
								} else {
									$submit = 'Download';
									$print_button = true;
								}
								echo '<p class="no-print">' . PHP_EOL;
								submit_button($submit,'primary','submit_top',false);
								echo '&nbsp;&nbsp;';
								if($recall) {
									submit_button($recall,'secondary','submit_top',false);
									echo '&nbsp;&nbsp;';
								}
								if($print_button) {
									//submit_button('Print','secondary','print_top',false);
									echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
									echo '&nbsp;&nbsp;';
								}
								submit_button('Reset Form','secondary','reset_top',false);
								if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
									echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
								}
								echo '<hr />' . PHP_EOL;
								echo '</p>' . PHP_EOL;
								echo $created;
							}
							break;
						case 'Download':
							$recall = 'Back to Retrieve Survey';
							echo '<h3>Preview:</h3>' . PHP_EOL;
							$created = generate_report(
								$api_selected,
								$survey_id,
								$survey_title,
								$value_statement_question_array,
								$demographic_question_array,
								$user_survey_title,
								$transparency,
								$threshold_color_above,
								$threshold_color_below,
								$significant_threshold,
								$colorization_threshold,
								$participatory_resistance,
								$report_0_cats,
								$value_statement_questions_master,
								$demographic_questions_master,
								$custom_demographics_array,
								$custom_demographics_master,
								$opin_credentials,
								$custom_demographics
							);
							if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
								$submit = 'Start Over';
							} else {
								$submit = 'Download';
								$print_button = true;
							}
							echo '<form name="download_print" method="post">' . PHP_EOL;
							echo '<p class="no-print">' . PHP_EOL;
							submit_button($submit,'primary','submit_top',false);
							echo '&nbsp;&nbsp;';
							if($recall) {
								submit_button($recall,'secondary','submit_top',false);
								echo '&nbsp;&nbsp;';
							}
							if($print_button) {
								//submit_button('Print','secondary','print_top',false);
								echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
								echo '&nbsp;&nbsp;';
							}
							submit_button('Reset Form','secondary','reset_top',false);
							if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
								echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
							}
							if(!isset($value_statement_questions)){
								$value_statement_questions = '';
							}
							if(!isset($demographic_questions)){
								$demographic_questions = '';
							}
							echo '<hr />' . PHP_EOL;
							echo '</p>' . PHP_EOL;
							echo $created;
							echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
							echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
							echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
							echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
							echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
							echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
							echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
							echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
							echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
							echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
							echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
							echo '<input type="hidden" id="transparency" name="transparency" value="' . $transparency . '" />' . PHP_EOL;
							echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
							echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
							echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
							echo '<span style="display:none"><output id="t_output">' . $transparency . '</output></span>' . PHP_EOL;
							break;
						case 'Print':
							$recall = 'Back to Retrieve Survey';
							echo '<h3>Preview:</h3>' . PHP_EOL;
							$created = generate_report(
								$api_selected,
								$survey_id,
								$survey_title,
								$value_statement_question_array,
								$demographic_question_array,
								$user_survey_title,
								$transparency,
								$threshold_color_above,
								$threshold_color_below,
								$significant_threshold,
								$colorization_threshold,
								$participatory_resistance,
								$report_0_cats,
								$value_statement_questions_master,
								$demographic_questions_master,
								$custom_demographics_array,
								$custom_demographics_master,
								$opin_credentials,
								$custom_demographics
							);
							if($created == '<h3>Report not found.</h3>' . PHP_EOL) {
								$submit = 'Start Over';
							} else {
								$submit = 'Download';
								$print_button = true;
							}
							echo '<form name="download_print" method="post">' . PHP_EOL;
							echo '<p class="no-print">' . PHP_EOL;
							submit_button($submit,'primary','submit_top',false);
							echo '&nbsp;&nbsp;';
							if($recall) {
								submit_button($recall,'secondary','submit_top',false);
								echo '&nbsp;&nbsp;';
							}
							if($print_button) {

								echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
								echo '&nbsp;&nbsp;';
							}
							submit_button('Reset Form','secondary','reset_top',false);
							if(!empty($participatory_resistance) && $created != '<h3>Report not found.</h3>' . PHP_EOL) {
								echo '<br /><a href="#pr_graphic_top">Jump to Participatory Resistance</a>' . PHP_EOL;
							}

							if(!isset($value_statement_questions)) {
								$value_statement_questions = '';
							}
							if(!isset($demographic_questions)){
								$demographic_questions = '';
							}
							echo '<hr />' . PHP_EOL;
							echo '</p>' . PHP_EOL;
							echo $created;
							echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
							echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
							echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
							echo '<input type="hidden" id="significant_threshold" name="significant_threshold" value="' . $significant_threshold . '" />' . PHP_EOL;
							echo '<input type="hidden" id="colorization_threshold" name="colorization_threshold" value="' . $colorization_threshold . '" />' . PHP_EOL;
							echo '<input type="hidden" id="threshold_color_above" name="threshold_color_above" value="' . $threshold_color_above . '" />' . PHP_EOL;
							echo '<input type="hidden" id="threshold_color_below" name="threshold_color_below" value="' . $threshold_color_below . '" />' . PHP_EOL;
							echo '<input type="hidden" id="value_statement_questions" name="value_statement_questions" value="' . $value_statement_questions . '" />' . PHP_EOL;
							echo '<input type="hidden" id="demographic_questions" name="demographic_questions" value="' . $demographic_questions . '" />' . PHP_EOL;
							echo '<input type="hidden" id="participatory_resistance" name="participatory_resistance" value="' . $participatory_resistance . '" />' . PHP_EOL;
							echo '<input type="hidden" id="report_0_cats" name="report_0_cats" value="' . $report_0_cats . '" />' . PHP_EOL;
							echo '<input type="hidden" id="transparency" name="transparency" value="' . $transparency . '" />' . PHP_EOL;
							echo '<input type="hidden" id="demographics_master" name="demographics_master" value="' . $demographic_questions_master . '" />' . PHP_EOL;
							echo '<input type="hidden" id="valuestatements_master" value="' . $value_statement_questions_master . '" />' . PHP_EOL;
							echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
							echo '<span style="display:none"><output id="t_output">' . $transparency . '</output></span>' . PHP_EOL;
							break;
					}
				}
				echo '<div class="no-print' . $override_class . '"' . $override_css . '>' . PHP_EOL;
				echo '<hr />' . PHP_EOL;
				submit_button($submit,'primary','submit',false);
				if(!empty($recall)) {
					echo '&nbsp;&nbsp;' . PHP_EOL;
					submit_button($recall,'secondary','submit',false);
				}
				echo '&nbsp;&nbsp;';
				if($print_button) {
					//submit_button('Print','secondary','print',false);
					echo '<input id="print_top" class="button button-secondary" type="button" value="Print" name="print_top" onclick="window.print();" />';
					echo '&nbsp;&nbsp;';
				}
				submit_button('Reset Form','secondary','reset',false);
				echo '</div>' . PHP_EOL;
				echo '</form>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
		}
	}

	function choose_api($api_array,$api_selected) {
		asort($api_array);
		echo '<h2>Choose an API</h2>' . PHP_EOL;
		echo '<form name="generate_report" method="post">' . PHP_EOL;
		echo '<select id="api_selected" name="api_selected">' . PHP_EOL;
		echo '<option value="">Select...</option>' . PHP_EOL;
		foreach($api_array as $aKey => $aValue) {
			$selected = '';
			if($api_selected == $aValue) $selected = ' selected';
			echo '<option value="' . $aValue . '"' . $selected . '>' . $aValue . '</option>' . PHP_EOL;
		}
		echo '</select>' . PHP_EOL;
		return 'Select API';
	}

	function choose_survey($api_selected,$survey_id,$opin_credentials=false) {
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
		wp_enqueue_script('questions', $plugindir . '/js/loading.js');
		$surveys = form_populate($opin_credentials);
		echo '<h2>Choose a Survey</h2>' . PHP_EOL;
		echo '<form name="enter_survey_id" method="post">' . PHP_EOL;
		echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
		echo '<input type="hidden" id="survey_title" name="survey_title" value="" />' . PHP_EOL;
		echo '<h3>API: ' . $api_selected . '</h3>' . PHP_EOL;
		echo '<div id="loading"><image src="' . $plugindir . '/assets/loading.gif" id="loading_gif" name="loading_gif" alt="loading..." /></div>' . PHP_EOL;
		echo '<div class="select_survey" style="display:none">';
		echo '<select id="survey_id" name="survey_id" onchange="populate_survey_title();">' . PHP_EOL;
		echo '<option value="">Select...</option>' . PHP_EOL;
		foreach($surveys as $sKey => $sValue) {
			$selected = ($survey_id == $sKey) ? ' selected="selected"' : '';
			echo '<option value="' . $sKey . '"' . $selected . '>' . $sValue . '</option>' . PHP_EOL;
		}
		echo '</select>'. PHP_EOL;
		echo '</div>' . PHP_EOL;
		echo '<script type="text/javascript" language="javascript">' . PHP_EOL;
		echo 'jQuery("#survey_title").val(jQuery("#survey_id option:selected").text());' . PHP_EOL;
		echo 'function populate_survey_title() {' . PHP_EOL;
		echo 'jQuery("#survey_title").val(jQuery("#survey_id option:selected").text());' . PHP_EOL;
		echo '}' . PHP_EOL;
		echo 'jQuery(document).ready(function() {' . PHP_EOL;
		echo 'jQuery("#loading").hide();' . PHP_EOL;
		echo 'jQuery(".select_survey").show();' . PHP_EOL;
		echo '});' . PHP_EOL;
		echo '</script>' . PHP_EOL;
		return 'Retrieve Survey';
	}

	function choose_survey_questions(
		$api_selected,
		$survey_id,
		$survey_title,
		$value_statement_question_array,
		$demographic_question_array,
		$user_survey_title,$transparency,
		$threshold_color_above,
		$threshold_color_below,
		$significant_threshold,
		$colorization_threshold,
		$participatory_resistance,
		$report_0_cats,
		$value_statement_questions_selected_array,
		$demographic_questions_selected_array,
		$value_statement_questions_master,
		$demographic_questions_master,
		$custom_demographics_array,
		$custom_demographics_master,
		$opin_credentials=false
	) {
		$value_statement_question_array = explode(',', sanitize_text_field($_REQUEST['value_statement_questions']) );
		$value_statement_questions_selected_array = $value_statement_question_array;
		$questions = retrieve_survey_questions($survey_id, $opin_credentials, array(), array());
		if ($questions !== '<h3>Report not found.</h3>' . PHP_EOL) {
			if(isset($_REQUEST['cd_list'])) {
				$cd_list_sanitized = sanitize_text_field($_REQUEST['cd_list']);
				echo "<input type='hidden' value='" . $cd_list_sanitized . "' id='cd_list'>" . PHP_EOL;
			}
			$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
			wp_enqueue_script('questions', $plugindir . '/js/questions.js');
			wp_enqueue_script('jscolor', $plugindir . '/js/jscolor/jscolor.js');
			wp_enqueue_script('colorization', $plugindir . '/js/colorization.js');
			wp_enqueue_script('rangeslider', $plugindir . '/js/rangeslider/rangeslider.js');
			add_action('wp_enqueue_scripts','opinionnaire_include_js');
			wp_enqueue_style('opinionnaire-pre-tables-css', $plugindir . '/css/pre_tables.css');
			wp_enqueue_style('rangeslidercss', $plugindir . '/js/rangeslider/rangeslider.css');
			$custom_demo_json = '';
			echo '<h2>Set Report Parameters</h2>' . PHP_EOL;
			echo '<form name="set_survey_params" method="post">' . PHP_EOL;
			echo '<input type="hidden" id="api_selected" name="api_selected" value="' . $api_selected . '" />' . PHP_EOL;
			echo '<input type="hidden" id="survey_id" name="survey_id" value="' . $survey_id . '" />' . PHP_EOL;
			echo '<input type="hidden" id="survey_title" name="survey_title" value="' . $survey_title . '" />' . PHP_EOL;
			echo '<input type="hidden" id="custom_demographics_master" name="custom_demographics_master" value="' . $custom_demographics_master . '" />' . PHP_EOL;
			echo '<h3>Survey Title</h3>' . PHP_EOL;
			echo '<input type="text" id="user_survey_title" name="user_survey_title" value="' . $user_survey_title . '" size=100 />' . PHP_EOL;
			echo '<h3>API: ' . $api_selected . '</h3>' . PHP_EOL;
			echo '<h3>Survey: ' . $survey_title . '</h3>' . PHP_EOL;
			echo '<div class="opin-input-numeric">' . PHP_EOL;
			echo '<label for="significant_threshold">Category Significance Threshold: </label> <output id="st_output">' . $significant_threshold . '</output>' . PHP_EOL;
			echo '<div>' . PHP_EOL;
			echo '0';
			echo '<input type="button" id="sig_threshold_decrease" name="sig_threshold_decrease" class="sig-threshold-modify" value="-" />';
			echo '<input id="significant_threshold" name="significant_threshold" type="range" value="' . $significant_threshold . '" step="1" min="0" max="50" />';
			echo '<input type="button" id="sig_threshold_increase" name="sig_threshold_increase" class="sig-threshold-modify" value="+" />';
			echo '50';
			echo '</div>' . PHP_EOL;
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-numeric">' . PHP_EOL;
			echo '<label for="colorization_threshold">Cell Colorization Threshold: </label> <output id="ct_output">' . $colorization_threshold . '</output>' . PHP_EOL;
			echo '<div>' . PHP_EOL;
			echo '0';
			echo '<input type="button" id="col_threshold_decrease" name="col_threshold_decrease" class="col-threshold-modify" value="-" />';
			echo '<input id="colorization_threshold" name="colorization_threshold" type="range" value="' . $colorization_threshold . '" step="1" min="0" max="50" />';
			echo '<input type="button" id="col_threshold_increase" name="col_threshold_increase" class="col-threshold-modify" value="+" />';
			echo '50';
			echo '</div>' . PHP_EOL;
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-color">' . PHP_EOL;
			echo '<label for="threshold_color_above">Threshold Color Above: </label>' . PHP_EOL;
			echo '<span class="notIeSafari"><input id="threshold_color_above" name="threshold_color_above" type="color" value="' . $threshold_color_above . '" /></span>' . PHP_EOL;
			echo '<span class="ieSafari" style="display:none"><input class="color" id="threshold_color_above_sf" name="threshold_color_above_sf" value="' . $threshold_color_above . '" /></span>' . PHP_EOL;
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-color">' . PHP_EOL;
			echo '<label for="threshold_color_below">Threshold Color Below: </label>' . PHP_EOL;
			echo '<span class="notIeSafari"><input id="threshold_color_below" name="threshold_color_below" type="color" value="' . $threshold_color_below . '" /></span>' . PHP_EOL;
			echo '<span class="ieSafari" style="display:none"><input class="color" id="threshold_color_below_sf" name="threshold_color_below_sf" value="' . $threshold_color_below . '" /></span>' . PHP_EOL;
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-text">' . PHP_EOL;
			echo '<label for="transparency">Threshold Color Transparency: </label><output id="t_output">' . $transparency . '</output><br /><br />' . PHP_EOL;
			echo '0.1';
			echo '<input type="button" id="transparency_decrease" name="transparency_decrease" class="transparency-modify" value="-" />';
			echo '<input id="transparency" name="transparency" type="range" value="' . $transparency . '" step="0.1" min="0.1" max="1.0" />';
			echo '<input type="button" id="transparency_increase" name="transparency_increase" class="transparency-modify" value="+" />';
			echo '1.0';
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-participatory">' . PHP_EOL;
			echo '<label for="participatory_resistance">Display Participatory Resistance: </label>' . PHP_EOL;
			$pr_checked = ($participatory_resistance) ? ' checked="checked"' : '';
			echo '<input id="participatory_resistance" name="participatory_resistance" type="checkbox" value="display"' . $pr_checked . ' />' . PHP_EOL;
			echo '</div><br />' . PHP_EOL;
			echo '<div class="opin-input-report-0-cats">' . PHP_EOL;
			echo '<label for="report_0_cats">Supress Survey Elements Categories with 0 responses: </label>' . PHP_EOL;
			$r0c_checked = ($report_0_cats) ? ' checked="checked"' : '';
			echo '<input id="report_0_cats" name="report_0_cats" type="checkbox" value="Supress"' . $r0c_checked . ' />' . PHP_EOL;
			echo '</div>' . PHP_EOL;
			//begin demographics inputs
			echo '<table id="choose_questions_demographics"><thead>' . PHP_EOL;
			echo '<tr><th colspan=2><h1>You must choose at least one Value Statement and at least one Demographic<h1></th></tr>' . PHP_EOL;
			echo '<tr><th class="opin-question-type">Demographic(s)</th></tr>' . PHP_EOL;
			echo '<tr><th class="choose-questions-radio demographics-radio">Include</th><th>Question</th></tr></thead><tbody>' . PHP_EOL;
			$checked = ' checked="checked"';
			$checked_demo_master = '';
			$checked_vs_master = '';
			if($demographic_questions_master) {
				$checked_demo_master = $checked;
			}
			if($value_statement_questions_master) {
				$checked_vs_master = $checked;
			}
			echo '<tr><td id="demographics-radio-master" class="choose-questions-radio demographics-radio">' . PHP_EOL;
			echo '<input type="checkbox" name="demographics_master" id="demographics_master" value="demographics_master" class="primary-check-box" onchange="javascript:checkAllQuestions(\'demographics\',false);"' . $checked_demo_master . ' />' . PHP_EOL;
			echo '</td><td>Select All Demographic Questions</td></tr>' . PHP_EOL;
			foreach($questions['demographic'] as $kQ => $vQ) {
				$checked_demo = '';
				if(in_array($kQ,$demographic_questions_selected_array)) {
					$checked_demo = $checked;
				}
				echo '<tr>' . PHP_EOL;
				echo '<td class="choose-questions-radio demographics-radio">' . PHP_EOL;
				echo '<input type="checkbox" name="question_' . $kQ . '" id="demographics_' . $kQ . '" value="demographics_' . $kQ . '" class="primary-check-box"' . $checked_demo . ' />' . PHP_EOL;
				echo '</td><td>' . PHP_EOL;
				echo '<label for="valuestatements_' . $kQ . '">' . $vQ . '</label>' . PHP_EOL;
				echo '</td></tr>' . PHP_EOL;
			}
			foreach($questions['wrong_subtype'] as $key => $subtype) {
				$subtype = strtolower($subtype);
				if(strpos($subtype,'zip code') != false || strpos($subtype,'zipcode') != false) {
					unset($questions['wrong_subtype'][$key]);
					echo '<tr>' . PHP_EOL;
					echo '<td class="choose-questions-radio demographics-radio">' . PHP_EOL;
					echo '</td><td>' . PHP_EOL;
					echo '<label for="valuestatements_zip">Zip Code</label>' . PHP_EOL;
					echo '</td></tr>' . PHP_EOL;
				}
			}
			echo '</tbody></table>' . PHP_EOL;
			//end demographics inputs
			//begin custom demographics inputs

			if(empty($questions['custom_demo'])) {
						$display = "display:none;";
					}
			$custom_demo_json = json_encode($questions['custom_demo']);
			$survey_id_sanitized = sanitize_text_field($_REQUEST['survey_id']);
			$retrieved_custom_demos = custom_demo_db('list', $_REQUEST['survey_id']);
			if(!isset($checked_custom_demo_master)) {
				$checked_custom_demo_master = '';
			}
				echo '<hr>';
				echo '<table id="choose_questions_custom_demographics" style="' . $display . '"><thead>' . PHP_EOL;
			echo '<tr><th class="opin-question-type">Custom Demographic(s)</th></tr>' . PHP_EOL;
			echo '<tr><th class="choose-questions-radio demographics-radio">Include</th><th>Title</th></tr></thead><tbody>' . PHP_EOL;
				echo '<tr><td id="demographics-radio-master" class="choose-questions-radio custom-demographics-radio">' . PHP_EOL;
			echo '<input type="checkbox" name="custom_demographics_master" id="custom_demographics_master" value="custom_demographics_master" class="primary-check-box" onchange="javascript:checkAllQuestions(\'custom_demographics\',false);"' . $checked_custom_demo_master . ' />' . PHP_EOL;
			echo '</td><td>Select All Custom Demographic Collections</td></tr>' . PHP_EOL;
			if($retrieved_custom_demos) {

					$i = 0;
				foreach($retrieved_custom_demos as $serial_obj) {

					$collections = unserialize($serial_obj->serial_custom_demographic);
					echo '<tr>' . PHP_EOL;
					echo '<td class="choose-questions-radio custom-demographics-radio">' . PHP_EOL;
					echo "<input type='checkbox' name='question_$i' id='custom_demographics_$i' value='$i' data-dbid='" . $serial_obj->id . "' data-json='" . json_encode($collections) . "' class='cd-check-box primary-check-box'/>" . PHP_EOL;
					echo '</td><td>' . PHP_EOL;
					echo '<label for="custom_demographics_' . $i . '">' . $collections['title'] . '</label>' . PHP_EOL;
					echo '<td class="edit-questions-radio edit-custom-demographics-radio">' . PHP_EOL;
					echo "<input type='checkbox' class='dashicons dashicon-edit' name='question_$i' id='edit_custom_demographics_$i' value='$i' data-dbid='" . $serial_obj->id . "' data-json='" . json_encode($collections) . "' class='primary-check-box'/>" . PHP_EOL;
					echo '</td>';
					echo '<td class="remove-questions-radio custom-demographics-radio">' . PHP_EOL;
					echo "<a href='#' id='remove_custom_demographics_$i' data-dbid='" . $serial_obj->id . "'/>x</a>" . PHP_EOL;
					echo '</td></tr>' . PHP_EOL;
					$i++;
				}
					echo '</tbody></table>' . PHP_EOL;
			}
			echo "<input type='hidden' id='cd_json' class='cd_json' value='" . str_replace("'", "&#39;", $custom_demo_json) . "' />" . PHP_EOL;
			echo '<input type="hidden" id="cd_select_count" class="cd_select_count" value="0" />' . PHP_EOL;
			echo '<input type="hidden" id="cdc_select_count" class="cd_select_count" value="0" />' . PHP_EOL;
			echo '<div id="custom_demo_select_div"></div>' . PHP_EOL;
			echo '<br />';
			echo '<table id="choose_questions_value_statements"><thead>' . PHP_EOL;
			echo '<tr><th class="opin-question-type">Value Statement(s)</th></tr>' . PHP_EOL;
			echo '<tr><th class="choose-questions-radio value-statements-radio">Include</th><th>Question</th></tr></thead><tbody>' . PHP_EOL;
			echo '<tr><td id="value-statements-radio-master" class="choose-questions-radio value-statements-radio">' . PHP_EOL;
			echo '<input type="checkbox" id="valuestatements_master" value="valuestatements_master" class="primary-check-box" onchange="javascript:checkAllQuestions(\'valuestatements\',false);"' . $checked_vs_master . ' />' . PHP_EOL;
			echo '</td><td>Select All Value Statement Questions</td></tr>' . PHP_EOL;
			foreach($questions['value_statement'] as $kQ => $vQ) {
				$checked_vs = '';
				if(in_array($kQ, $value_statement_questions_selected_array)) {
					$checked_vs = $checked;
				}
				echo '<tr>' . PHP_EOL;
				echo '<td class="choose-questions-radio value-statements-radio">' . PHP_EOL;
				echo '<input type="checkbox" name="question_' . $kQ . '" id="valuestatements_' . $kQ . '" value="valuestatements_' . $kQ . '" class="primary-check-box"' . $checked_vs . ' />' . PHP_EOL;
				echo '</td><td>' . PHP_EOL;
				echo '<label for="valuestatements_' . $kQ . '">' . $vQ . '</label>' . PHP_EOL;
				echo '</td></tr>' . PHP_EOL;
			}
			echo '</tbody></table>' . PHP_EOL;
			//end value statements inputs
			//begin no keycode declarations (hidden by default)
			if(!empty($questions['no_keycode'])) {
				echo '<h3 id="missing-keycodes-button" class="no-link"><span id="missing-keycodes-text">Show</span> Survey elements with missing keycodes</h3>' . PHP_EOL;
				echo '<div class="unused-elements missing-keycodes" style="display:none">' . PHP_EOL;
				echo '<table class="not-included"><tbody>' . PHP_EOL;
				foreach($questions['no_keycode'] as $kNK => $vNK) {
					echo '<tr><td>' . $kNK . '</td><td>' . $vNK . '</td></tr>' . PHP_EOL;
				}
				echo '</tbody></table>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
			//end no keycode declarations
			//begin keycode out of bounds declarations (hidden by default)
			if(!empty($questions['keycode_out_of_bounds'])) {
				echo '<h3 id="keycodes-out-of-bounds-button" class="no-link"><span id="keycodes-out-of-bounds-text">Show</span> Survey elements with keycodes greater than 9</h3>' . PHP_EOL;
				echo '<div class="unused-elements keycodes-out-of-bounds" style="display:none">' . PHP_EOL;
				echo '<table class="not-included"><tbody>' . PHP_EOL;
				foreach($questions['keycode_out_of_bounds'] as $kKOOB => $vKOOB) {
					echo '<tr><td>' . $kKOOB . '</td><td>' . $vKOOB . '</td></tr>' . PHP_EOL;
				}
				echo '</table></tbody>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
			//end keycode out of bounds declarations
			//begin wrong subtype declarations (hidden by default)
			if(!empty($questions['wrong_subtype'])) {
				echo '<h3 id="wrong-subtype-button" class="no-link"><span id="wrong-subtype-text">Show</span> Survey elements not of subtype Radio or Menu</h3>' . PHP_EOL;
				echo '<div class="unused-elements wrong-subtype" style="display:none">' . PHP_EOL;
				echo '<h5><i>Survey elements of subtype Media, Instructions, Hidden, and Table are also excluded and not in this list.</i></h5>' . PHP_EOL;
				echo '<table class="not-included"><tbody>' . PHP_EOL;
				foreach($questions['wrong_subtype'] as $kWS => $vWS) {
					echo '<tr><td>' . $kWS . '</td><td>' . $vWS . '</td></tr>' . PHP_EOL;
				}
				echo '</tbody></table>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
			//end wrong subtype declarations
			return 'Generate Report';
		} else {
			echo $questions;
			return 'Start Over';
		}
	}

	//create cross tab report
	function generate_report(
		$api_selected,
		$survey_id,
		$survey_title,
		$value_statement_question_array,
		$demographic_question_array,
		$user_survey_title,
		$transparency,
		$threshold_color_above,
		$threshold_color_below,
		$significant_threshold,
		$colorization_threshold,
		$participatory_resistance,
		$report_0_cats,
		$value_statement_questions_master,
		$demographic_questions_master,
		$custom_demographics_array,
		$custom_demographics_master,
		$opin_credentials=false,
		$custom_demographics=null 
	) {
		//define plugin directory
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));

		//include javascript files
		wp_enqueue_script('pol_generation', $plugindir . '/js/pol_generation.js');
		wp_enqueue_script('colorization', $plugindir . '/js/colorization.js');
		wp_enqueue_script('anchor', $plugindir . '/js/anchor.js');
		wp_enqueue_script('rangeslider', $plugindir . '/js/rangeslider/rangeslider.js');
		//add javascript files to the page
		add_action('wp_enqueue_scripts','opinionnaire_include_js');
		//include css files
		wp_enqueue_style('rangeslidercss', $plugindir . '/js/rangeslider/rangeslider.css');
		wp_enqueue_style('opinionnaire-tables-css', $plugindir . '/css/tables.css');

		$display_prim_q = array();//skeleton array to hold questions and totals
		$display_sec_q = array();
		$pat_res = array();//array to hold participatory resistance values
		$con_totals = array();
		$alt_prim_questions = array();
		$kc_prim_questions = array();
		$kc_prim_legends = array();
		$alt_sec_questions = array();
		$kc_sec_questions = array();
		$abstain_object_keycodes = array(8,9);
		$kc_prim_distilled = array();
		$kc_prim_distilled_reverse = array();
		$kc_sec_distilled = array();
		$kc_sec_distilled_reverse = array();
		$cross_tab = '';//string to house output
		//set default threshold colors to white if not set in the call above
		$color_above = (!empty($threshold_color_above)) ? $threshold_color_above : '#ffffff';
		$color_below = (!empty($threshold_color_below)) ? $threshold_color_below : '#ffffff';
		if(isset($survey_id)) {
			if($opin_credentials !== false) {//get live data
				//psuedo-raw selected questions
				$questions = retrieve_survey_questions($survey_id,$opin_credentials,$value_statement_question_array,$demographic_question_array);
				//raw survey responses
				$responses = retrieve_survey($survey_id,$opin_credentials);
			} else {//get sample data
				//psuedo-raw selected questions
				$questions = retrieve_survey_questions($survey_id,false,$value_statement_question_array,$demographic_question_array);
				//raw survey responses
				$responses = retrieve_survey($survey_id,false);
			}
		}
		//validate questions and responses are not empty
		if(empty($questions) || $questions['result_ok'] != 1 || $questions['total_count'] < 1) return '<h3>Report not found.</h3>' . PHP_EOL;
		if(empty($responses) || $responses->result_ok != 1 || $responses->total_count < 1) return '<h3>Report not found.</h3>' . PHP_EOL;

		foreach($questions as $qType => $qArray) {
			switch($qType) {
				case 'value_statement':
					foreach($qArray as $qsKey => $qsValue) {
						$display_prim_q[$qsKey]['title'] = $qsValue->title->English;
						$display_prim_q[$qsKey]['response']['numeric_total'] = 0;
						$answer_count = 0;
						foreach($qsValue->options as $qsoKey => $qsoValue) {
							$prim_options[$qsKey][$qsoValue->value] = 0;
							$display_prim_q[$qsKey]['response'][$qsoValue->value] = 0;
							$alt_prim_questions[$qsoValue->title->English] = $qsoValue->value;
							$cd_prim_questions[$qsoValue->title->English] = $qsoValue->value;
							$kc_prim_questions[$qsKey][$answer_count]['title'] = $qsoValue->title->English;
							$kc_prim_questions[$qsKey][$answer_count]['value'] = $qsoValue->value;
							$kc_prim_questions[$qsKey][$answer_count]['keycode'] = $qsoValue->properties->keycodes[0];
							$answer_count++;
							//build legend scale arrays for value_statement questions
							if(!in_array($qsoValue->properties->keycodes[0],$abstain_object_keycodes)) {
								$kc_prim_legends[$qsKey][$qsoValue->properties->keycodes[0]] = $qsoValue->title->English;
								$cd_prim_legends[$qsKey][$qsoValue->properties->keycodes[0]] = $qsoValue->title->English;
							}
						}
					}
					break;
				case 'demographic':
					foreach($qArray as $qsKey => $qsValue) {
						$display_sec_q[$qsKey]['title'] = $qsValue->title->English;
						$answer_count = 0;
						foreach($qsValue->options as $qsoKey => $qsoValue) {
							$display_sec_q[$qsKey]['response'][$qsoValue->value] = array();
							$alt_sec_questions[$qsoValue->title->English] = $qsoValue->value;
							$kc_sec_questions[$qsKey][$answer_count]['title'] = $qsoValue->title->English;
							$kc_sec_questions[$qsKey][$answer_count]['value'] = $qsoValue->value;
							$kc_sec_questions[$qsKey][$answer_count]['keycode'] = $qsoValue->properties->keycodes[0];
							$answer_count++;
						}
					}
					break;
			}
		}
		foreach($display_prim_q as $dpqKey => $dpqValue) {
			foreach($display_sec_q as $dsqKey => $dsqValue) {
				$display_prim_q[$dpqKey][$dsqKey] = $dsqValue;
				foreach($display_prim_q[$dpqKey][$dsqKey]['response'] as $subKey => $subValue) {
					$display_prim_q[$dpqKey][$dsqKey]['response'][$subKey] = $prim_options[$dpqKey];
				}
			}
		}
		$display_cd_q = $display_prim_q;
		foreach($kc_prim_questions as $kpqKey => $kpqValue) {
			foreach($kpqValue as $kpqvkey => $kpqvValue) {
				$kc_prim_distilled[$kpqKey][$kpqvValue['keycode']] = $kpqvValue['value'];
				$kc_prim_distilled_reverse[$kpqKey][$kpqvValue['value']] = $kpqvValue['keycode'];
			}
		}
		foreach($kc_sec_questions as $ksqKey => $ksqValue) {
			foreach($ksqValue as $ksqvKey => $ksqvValue) {
				$kc_sec_distilled[$ksqKey][$ksqvValue['keycode']] = $ksqvValue['value'];
				$kc_sec_distilled_reverse[$ksqKey][$ksqvValue['value']] = $ksqvValue['keycode'];
			}
		}

		// Functionality below this line is responsible for report generation.

			foreach($custom_demographics as $collection => $group) {
				// Getting Collections
				foreach($group as $group => $the_questions) {
					// Getting Groups
					foreach( $the_questions as $question => $params) {
						// Getting Questions
						foreach( $params as $q => $v) {
							// Getting conditions
							if($q == 6) {
								$params_arr[$q][$v['conditions']] = $v['inner_text'];
							}
							foreach($v['inner_text'] as $id => $val) {
								$custom_for_parse['custom_demographic'][$collection]['title'] = $custom_demographics[$collection]['title'];
								$custom_title = $custom_demographics[$collection][$group]['title'];
								$custom_for_parse['custom_demographic'][$collection][$custom_title][$q][$v['conditions']][$id] = $val;
							}
						}
					}
				}
			}

			foreach($custom_for_parse as $key => $value) {
				foreach($value as $the_collection => $the_qs) {
					unset($the_qs['title']);
				}
			}

		// This next block of code applies the logical decisions that make $the_matrix happen.
		$cd_list = '';
		foreach($_REQUEST['custom_demo_select'] as $cd) {
			$cd_list .= sanitize_text_field($cd['db_id']) . ',';
		}
		echo "<input type='hidden' value='$cd_list' name='cd_list'>";

		$cd_q_array = $display_prim_q;

		if(!empty($display_prim_q)) {
			$cross_tab .= '<div class="printable">' . PHP_EOL;
			$cross_tab .= "<h1>$user_survey_title</h1>" . PHP_EOL;
			$cross_tab .= "<h2>$survey_title</h2>" . PHP_EOL;
			$processed = 0;
			//iterate over the survey responses and plug the numbers in the skeleton
			$processed_count = 0;
			$total = count($response->data);
			foreach($responses->data as $rKey => $rValue) {//foreach response set as numeric key => responses
				$processed_count++;
				$cdValue = $rValue;
				foreach($value_statement_question_array as $pValue) {//foreach value statement question as value
					if($pValue != 'master') {
						//put the question number into the form the response object can match to "[question($question_number)]"
						$p_q = '[question(' . $pValue . ')]';
						//if($rValue->$p_q != 'Not Identified') {
						if(isset($display_prim_q[$pValue]['response'][$rValue->$p_q]) || isset($display_prim_q[$pValue]['response'][$alt_prim_questions[$rValue->$p_q]])) {
							$this_answer = $rValue->$p_q;
						} else {
							$this_answer = 'Abstain';
						}
						if(isset($display_prim_q[$pValue]['response'][$alt_prim_questions[$rValue->$p_q]])) $this_answer = $alt_prim_questions[$rValue->$p_q];
						$display_prim_q[$pValue]['response'][$this_answer]++;
						$display_prim_q[$pValue]['response']['numeric_total']++;
						foreach($demographic_question_array as $sValue) {//foreach demographic question as value
							//put the question number  into the form the response object can match to "[question($question_number)]"
							$s_q = '[question(' . $sValue . ')]';
							$found = false;
							foreach($display_prim_q[$pValue][$sValue]['response'] as $ssubKey => $ssubValue) {
								if($rValue->$s_q == $ssubKey || $alt_sec_questions[$rValue->$s_q] == $ssubKey) {

									$this_sub_answer = $rValue->$s_q;
									if($alt_sec_questions[$rValue->$s_q] == $ssubKey) $this_sub_answer = $alt_sec_questions[$rValue->$s_q];
									$display_prim_q[$pValue][$sValue]['response'][$this_sub_answer][$this_answer]++;
									$found = true;
								}
							}
						}
					//}
					}
				}
					foreach($custom_for_parse['custom_demographic'] as $collection => $cd_collection) {
						$not_identified = true;
						foreach($value_statement_question_array as $pValue) {//foreach value statement question as value
							unset($cd_collection['title']);
							$group_count = 0;
							if(isset($old_passed)) {
								unset($old_passed);
							}
							$display_cd_q1[$pValue]['title'] = $cd_q_array[$pValue]['title'];
							$skip = false;
							foreach($cd_collection as $cd_collection_key => $cd_collection_value) { // collection foreach
								$check_passed = NULL;
								$this_sub_answer = $custom_for_parse['custom_demographic'][$collection]['title'];
								$display_cd_q1[$pValue][$collection]['title'] = $this_sub_answer;
								foreach($cd_q_array[$pValue]['response'] as $response => $q){
									if(!isset($display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$response])) {
										if(is_null($display_cd_q1[$pValue]['response'][$response]) && ($response !== 'numeric_total')) {
											$display_cd_q1[$pValue]['response'][$response] = 0;
										}
										if($response != 'numeric_total') {
											$display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$response] = 0;
										}
									}
								}
								foreach($cd_collection_value as $cd_group_key => $cd_group_value) { // group foreach
									if(!$skip) {
										if(isset($cd_collection_value['title'])) {
											$display_cd_q1[$pValue][$cd_group_key]['title'] = $cd_group_value['title'];
										}
										if($new_group == true) {
											$new_group = false;
											$check_passed;
										}

										$cd_pq = '[question(' . $pValue. ')]';

										if(isset($display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$rValue->$cd_pq]) || isset($display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$alt_prim_questions[$rValue->$p_q]])) {
											$this_answer = $rValue->$cd_pq;
										} else {
											$this_answer = 'Abstain';
										}
										$cd_q = '[question(' . $cd_group_key . ')]';
										foreach($cd_group_value as $cd_question_key => $cd_question_value) { // Custom Demographic
											if((isset($check_passed) && $check_passed) || is_null($check_passed)) {
												if($cd_question_key == 3 || $cd_question_key == 1) {
													if(in_array($cdValue->$cd_q,$cd_question_value)) {
														$check_passed = true;
													} else {
														$check_passed = false;
													}
												} elseif($cd_question_key == 4 || $cd_question_key == 2) {
													if(!in_array($cdValue->$cd_q,$cd_question_value)) {
														$check_passed = true;
													} else {
														$check_passed = false;
													}
												} elseif($cd_question_key == 5) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if(($q_value < $cdValue->$cd_q) && (($q_value != $cdValue->$cd_q))) {
															$check_passed = true;
														}
													}
												} elseif($cd_question_key == 6) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if(($q_value < $cdValue->$cd_q) || (($q_value == $cdValue->$cd_q))) {
															$check_passed = true;
														}
													}
												} elseif($cd_question_key == 7) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if(($q_value > $cdValue->$cd_q) && (($q_value != $cdValue->$cd_q))) {
															$check_passed = true;
														}
													}
												} elseif($cd_question_key == 8) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if(($q_value > $cdValue->$cd_q) || (($q_value == $cdValue->$cd_q))) {
															$check_passed = true;
														}
													}
												} elseif($cd_question_key == 13) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if((preg_match($q_value,$cdValue->$cd_q) != 0) || (preg_match($q_value,$cdValue->$cd_q) != false)) {
															$check_passed = true;
														}
													}
												} elseif($cd_question_key == 13) {
													$check_passed = false;
													foreach($cd_question_value as $q_key => $q_value) {
														if((preg_match($q_value,$cdValue->$cd_q) == 0) || (preg_match($q_value,$cdValue->$cd_q) != false)) {
															$check_passed = true;
														}
													}
												}
											}
										} // end custom demographic value question foreach
									}
								} // end group foreach
								if($check_passed) {
									if(is_null($display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$this_answer])) {
										$display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$this_answer] = 0;
									}
								if(is_null($display_cd_q1[$pValue]['response'][$this_answer])) {
									$display_cd_q1[$pValue]['response'][$this_answer] = 0;
								}
									$display_cd_q1[$pValue]['response'][$this_answer] ++;
									$display_cd_q1[$pValue][$collection]['response'][$cd_collection_key][$this_answer] ++;
									$skip = true;
									$not_identified = false;
								}
								$new_group = true;
								$group_count ++;
							} // end collection foreach
							if($not_identified) {
								foreach($display_cd_q1[$pValue]['response'] as $cd_question_key => $val) {
									if(is_null($display_cd_q1[$pValue][$collection]['response']['Not Identified'][$cd_question_key]) && ($cd_question_key != 'numeric_total') && ($cd_question_key != 'Not Identified')) {
										$display_cd_q1[$pValue][$collection]['response']['Not Identified'][$cd_question_key] = 0;
									}
								}
								if(is_null($display_cd_q1[$pValue]['response'][$this_answer])) {
									$display_cd_q1[$pValue]['response'][$this_answer] = 0;
								}
								$display_cd_q1[$pValue]['response'][$this_answer] ++;
								if(($this_answer !== 'Not Identified') && ($this_answer !== 'numeric_total')) {
									$display_cd_q1[$pValue][$collection]['response']['Not Identified'][$this_answer] ++;
								}
							}
						} // end value statement foreach
					} // end custom_for_parse foreach
					if(is_null($display_cd_q1[$pValue]['response']['numeric_total'])) {
						$display_cd_q1[$pValue]['response']['numeric_total'] = 1;
					}
					$total_count = $display_cd_q1[$pValue]['response']['numeric_total'] ++;
					$countMe = 0;
				}
				foreach($display_cd_q1 as $cKey => $cValue) {
					$display_cd_q1[$cKey]['response']['numeric_total'] = $total_count;
				}


			foreach($display_prim_q as $mKey => $mValue) {
				if(count($mValue) > 2) {
					$cross_tab .= '<div class="prime-question">' . PHP_EOL;
					$cross_tab .= '<h3>' . $mValue['title'] . '</h3>' . PHP_EOL;
				}
				$count_p_r = count($mValue['response']) - 1;//number of prime question header columns
				$count_p_r += 3;//add 3 for TOTAL, CATEGORY, and PC or MPC RATINGtm and row Polarization button hangs outside of table
				$legend = "<tr><th colspan=$count_p_r>" . PHP_EOL;
				$legend_first = reset($kc_prim_legends[$mKey]);
				$legend_count = count($kc_prim_legends[$mKey]);
				$cross_tab_type = '';
				$rating_type = '';
				//we define the question type based on the number of possible responses (excluding abstain and object which may or may not be present but typically are)
				switch($legend_count) {
					case 2:
						$cross_tab_type = 'yesno';
						$rating_type = 'PC';
						break;
					case 5:
						$cross_tab_type = 'likert5';
						$rating_type = 'MPC';
						break;
					case 7:
						$cross_tab_type = 'likert7';
						$rating_type = 'MPC';
						break;
				}
				//create the legend for the crosstab based on the possible responses
				$patterns = array(0=>'/\(/',1=>'/\d{1,}/',2=>'/\)/');
				$replace = array(0=>'',1=>'',2=>'');
				$legend_first = preg_replace($patterns,$replace,$legend_first);
				$legend_text = $legend_first . " ";
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$legend_text .= "($kcplKey) ";
					$final_legend = $kcplValue;
				}
				//finalize legend
				$final_legend = preg_replace($patterns,$replace,$final_legend);
				$legend_text .= " $final_legend";
				$legend .= $legend_text . "</th><th></th></tr>" . PHP_EOL;
				//create the response headers for the crosstab table
				$categories = "<tr><th>TOTAL</th>" . PHP_EOL;
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$categories .= "<th>($kcplKey)</th>" . PHP_EOL;
				}
				//add abstain and/or object to the response headers if they exist for this question
				foreach($kc_prim_distilled[$mKey] as $kpdMKkey => $kpdMKvalue) {
					if($kpdMKkey == 8 || $kpdMKkey == 9) {
						if($kpdMKvalue == 8) $kpdMKvalue = 'Abstain';
						if($kpdMKvalue == 9) $kpdMKvalue = 'Object';
						$categories .= "<th>" . $kpdMKvalue . "</th>" . PHP_EOL;
					}
				}
				//finalize header row of crosstab table
				$categories .= "<th>CATEGORY</th><th>$rating_type RATING&#8482;</th><th></th>" . PHP_EOL;
				$categories .= "</tr></thead><tbody>" . PHP_EOL;
				$sec_output = '';
				$sec_row = '';
				$the_matrix = array();//this is the master array that will contain all of the data to be output in a standardized format

				foreach($mValue as $msKey => $msValue) {
					if(in_array($msKey,$demographic_question_array)) {
						$overall_total = $mValue['response']['numeric_total'] - $mValue['response']['Not Identified'];
						$sig_lower_limit = '';
						$int_significant_threshold = (int)$significant_threshold;
						if(!empty($int_significant_threshold) && !empty($overall_total)) {
							$sig_lower_limit = opin_round($significant_threshold * $overall_total * 0.01,true);//the number in the vertical TOTAL column that a category has to be >= to qualify for significance
						}

						// Pulls individual responses from demographic.

						foreach($msValue as $mssKey => $mssValue) {
							switch($mssKey) {
								case 'title':
									$title_middle = '';
									$title_before = '<table id="question-' . $msKey . '-' . $mKey . '-table" class="opin-data';
									$title_after = '"><thead>' . PHP_EOL;
									$title_after .= "<tr style='background-color:#00CCFF;color:#FFFFFF'><th colspan=$count_p_r class='secondary_question'>$mssValue</th><td></td><tr>" . PHP_EOL;
									$title_after .= $legend;
									$title_after .= $categories;
									break;
								case 'response':

									// The response element contains a copy of all of the individual possible responses to be modified.

									$column_total = array();
									$column_count = 0;
									$abstain_master = 0;
									$object_master = 0;
									$neutral_master = 0;
									$yes_master = 0;
									$over_total = 0;
									foreach($mssValue as $secKey => $secValue) {
										$abstain_row = 0;
										$object_row = 0;
										$neutral_row = 0;
										$yes_row = 0;
										$sec_total = array_sum($secValue);
										if($sec_total > 0 || empty($report_0_cats)) {
											$the_matrix[$secKey . '-mK' . $msKey][0] = $sec_total;
											$the_matrix[$secKey . '-mK' . $msKey]['abstain'] = 0;//$secValue['Abstain'];
											$the_matrix[$secKey . '-mK' . $msKey]['object'] = 0;//$secValue['Object'];
											$the_matrix[$secKey . '-mK' . $msKey]['positive'] = 0;
											$the_matrix[$secKey . '-mK' . $msKey]['neutral'] = 0;
											$the_matrix[$secKey . '-mK' . $msKey]['negative'] = 0;
											$matrix_count = 1;
											$sec_thresh_perc  = opin_round(($sec_total / $overall_total) * 100,true);
											$sec_thresh_color = '';
											if($sec_thresh_perc > $int_significant_threshold && !empty($threshold_color_above) && $int_significant_threshold !== 0) {
												$sec_thresh_color = ' style="background-color:' . $threshold_color_above . '"';
												if(empty($title_middle)) $title_middle = ' colorized';
											}
											$over_total += $sec_total;
											$sec_row .= '<tr><td class="opin-numeric">' . $sec_total . '</td>' . PHP_EOL;
											if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
											$column_total[$column_count] += $sec_total;
											foreach($secValue as $primKey => $secSub) {
												$column_count++;
												if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
												$column_total[$column_count] += $secSub;
												$abstain = 0;
												$object = 0;
												if($primKey === 'Abstain' || $primKey == 8 || $primKey == '8') {
													$abstain = $secSub;
													$abstain_master += $secSub;
													$abstain_row += $secSub;
													$the_matrix[$secKey . '-mK' . $msKey]['abstain'] += $secSub;
												} elseif($primKey === 'Object' || $primKey == 9 || $primKey == '9') {
													$object = $secSub;
													$object_master += $secSub;
													$object_row += $secSub;
													$the_matrix[$secKey . '-mK' . $msKey]['object'] += $secSub;
												}
												switch($cross_tab_type) {
													case 'yesno':
														if($primKey === 'Yes' || $primKey == 1 || $primKey == '1' || $primKey == $questions['value_statement'][$mKey]->options[0]->value) {
															$yes_row += $secSub;
															$yes_master += $secSub;
															$the_matrix[$secKey . '-mK' . $msKey]['positive'] += $secSub;
														}
														break;
													case 'likert5':
														//evaluate $primKey against $kc_prim_questions array
														if(isset($kc_prim_distilled_reverse[$mKey][$primKey])) {//by title
															switch($kc_prim_distilled_reverse[$mKey][$primKey]) {
																case '5':
																case '4':
																	$yes_row += $secSub;
																	$yes_master += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	break;
																case '3':
																	$neutral_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '2':
																case '1':
																	$the_matrix[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														} elseif(isset($kc_prim_distilled[$mKey][$primKey])) {//by keycode
															switch($primKey) {
																case '5':
																case '4':
																	$yes_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '3':
																	$neutral_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '2':
																case '1':
																	$the_matrix[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														}
														break;
													case 'likert7':
														//evaluate $primKey against $kc_prim_questions array
														if(isset($kc_prim_distilled_reverse[$mKey][$primKey])) {//by title
															switch($kc_prim_distilled_reverse[$mKey][$primKey]) {
																case '7':
																case '6':
																case '5':
																	$yes_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '4':
																	$neutral_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '3':
																case '2':
																case '1':
																	$the_matrix[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														} else if(isset($kc_prim_distilled[$mKey][$primKey])) {//by keycode
															switch($primKey) {
																case '7':
																case '6':
																case '5':
																	$yes_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '4':
																	$neutral_row += $secSub;
																	$the_matrix[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '3':
																case '2':
																case '1':
																	$the_matrix[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														}
														break;
												}
												$sec_perc = 0;
												if($sec_total != 0 && $secSub != 0) {
													$sec_perc_raw = ($secSub/$sec_total) * 100;
													$sec_perc = opin_round($sec_perc_raw);
												}
												$td_thresh_color = '';
												if(!empty($sec_thresh_color) && $colorization_threshold !== '') {
													if($sec_perc < $colorization_threshold) {
														$td_thresh_color = ' style="background-color:' . $threshold_color_below . '"';
													} else {
														$td_thresh_color = ' style="background-color:' . $threshold_color_above . '"';
													}
												}
												$the_matrix[$secKey . '-mK' . $msKey][$matrix_count] = $sec_perc;
												//this would give us a count by value statement option
												$the_matrix[$secKey . '-mK' . $msKey][$matrix_count . '_'] = $secSub;
												$matrix_count++;
												$sec_row .= '<td class="opin-numeric"><div class="opin-table-text">' . $sec_perc . '%</div><div class="opin-table-color"' . $td_thresh_color . '></div></td>' . PHP_EOL;
											}



											if(isset($cd_matrix)) {
												foreach($cd_matrix as $name => $cd) {
													$the_matrix[$name] = $cd;
												}
											}
											//make category ($secKey) lowercase dashed
											$this_cat = strtolower($secKey);
											$this_cat = str_replace(' ','--',$secKey);
											$this_cat = preg_replace('/[^a-z0-9_-]+/i','',$this_cat);
											$sec_row .= "<td id='$this_cat' class='categorical'>$secKey</td>" . PHP_EOL;
											$sec_ao_total = $abstain_row + $object_row;
											$sec_ao_perc = 0;
											$sec_pol_perc = 0;
											if($sec_total > 0) $sec_pol_perc = 100;
											$sec_mpc_perc = 0;
											if($overall_total > 0) {
												if($sec_ao_total > 0) {
													$sec_ao_perc = opin_round(($sec_ao_total/$sec_total) * 100);
													$sec_pol_perc = 100 - $sec_ao_perc;
												}
												if($sec_total > 0) {
													$sec_mpc_total = $sec_total - $sec_ao_total - $neutral_row;
													if($yes_row > 0 && $sec_mpc_total > 0) {
														$sec_mpc_perc = opin_round(($yes_row/$sec_mpc_total) * 100);
													}
												}
											}
											//if the cateogry response total is 0, we don't include this category in the participatory resistance graphic
											if($sec_total > 0) {
												//if the user set a significant threshold and this category does not meet or exceed that threshold, we don't include it in the participatory resistance graphic
												if($int_significant_threshold > 0 && $sec_thresh_perc > $significant_threshold) {
													$con_totals[$secKey][] = $sec_mpc_perc;//consensus
												} else {
													$con_totals[$secKey][] = $sec_mpc_perc;//consensus
												}
											}
											$sec_row .= "<td class='pc-rating'>( $sec_pol_perc% - $sec_mpc_perc%)</td>" . PHP_EOL;
											$sec_pol_button = '';
											if($sec_total > 0) {
												//pass to onclick params: category, demo question id, value statement question id, poloarization percentage, consensus percentage, abstain/object percentage
												//targets table id: question-$demo question id-$value statement question id-table
												$sec_pol_button = '<input type="button" value="+" id="button-' . $msKey . '-' . $mKey . '-' . $this_cat . '" onclick="generate_pol(\'' . $this_cat . '\',' . $msKey . ',' . $mKey . ',' . $sec_pol_perc . ',' . $sec_mpc_perc . ',' . $sec_ao_perc . ')" />';
											}
											$sec_row .= "<td>$sec_pol_button</td>" . PHP_EOL;
											$sec_row .= "</tr>" . PHP_EOL;
											$column_count = 0;
											if(empty($report_0_cats)) {
												$sec_output .= $sec_row;
											} else if($sec_total > 0) {
												$sec_output .= $sec_row;
											}
										}
										$sec_row = '';
									}
									$sec_output .= "</tbody><tfoot><tr>" . PHP_EOL;
									foreach($column_total as $ctKey => $cTotal) {
										if($ctKey == 0) {
											$sec_output .= "<td class='opin-numeric'>$cTotal</td>" . PHP_EOL;
										} else {
											$cPerc = 0;
											if($cTotal > 0) {
												$cPerc = opin_round(($cTotal/$overall_total) * 100);
											}
											$sec_output .= "<td class='opin-numeric'>$cPerc%</td>" . PHP_EOL;
										}
									}
									$sec_output .= "<td>Total</td>" . PHP_EOL;
									$ao_total = $abstain_master + $object_master;
									$polar_perc = 0;
									if($overall_total > 0) $polar_perc = 100;
									$ao_perc = 0;
									$mpc_perc = 0;
									if($overall_total > 0) {
										if($ao_total > 0) {
											$ao_perc = opin_round(($ao_total/$overall_total) * 100);
											$polar_perc = 100 - $ao_perc;
										}
										$mpc_total = $overall_total - $ao_total - $neutral_master;
										if($yes_master > 0 && $mpc_total > 0) {
											$mpc_perc = opin_round(($yes_master/$mpc_total) * 100);
										}
									}
									$sec_output .= "<td class='opin-table-total-rating'>( $polar_perc% - $mpc_perc%)</td></tr></tfoot>" . PHP_EOL;
									break;
							}
						}
						//This is where we start the new table output
						$secondary_output = '';
						$footer = '';
						$this_sig_thresh = 0;
						$column_thresh = array();
						$column_thresh[0] = 0;
						foreach($column_total as $ctKey => $cTotal) {
							if($ctKey == 0) {
								$footer .= "<td class='opin-numeric'>$cTotal</td>" . PHP_EOL;
								if($significant_threshold > 0) {
									//$sig_thresh * 0.01 [because sig_thresh is a percent] * overall_total tells us how much a row has to be to qualify in terms of raw count
									$this_sig_thresh = opin_round($overall_total * ($significant_threshold * 0.01),true);
									$column_thresh[0] = $this_sig_thresh;
								}
							} else {
								$cPerc = 0;
								if($cTotal > 0) {
									$cPerc = opin_round(($cTotal/$overall_total) * 100);
								}
								$column_thresh[$ctKey] = 0;
								if($colorization_threshold > 0) {
									if($cTotal > 0) {
										$column_thresh[$ctKey] = $cPerc;
									}
								}
								$footer .= "<td class='opin-numeric'>$cPerc%</td>" . PHP_EOL;
							}
						}
						if($this_sig_thresh) {
							$refactor = false;
							reset($the_matrix);
							$broken_keys = array();
							foreach($the_matrix as $tmKey => $tmValue) {
								$this_broken_key = explode('-mK',$tmKey);
								$broken_keys[$this_broken_key[1]] = $this_broken_key[1];
								if($tmKey != 'Not Identified-mK' . $this_broken_key[1]) {
									if($tmValue[0] < $this_sig_thresh) {
										if(!isset($the_matrix['Not Identified-mK' . $this_broken_key[1]])) $the_matrix['Not Identified-mK' . $broken_key[1]] = array();
										foreach($tmValue as $tmvKey => $tmvValue) {
											if(!isset($the_matrix['Not Identified-mK' . $this_broken_key[1]][$tmvKey])) $the_matrix['Not Identified-mK' . $this_broken_key[1]][$tmvKey] = 0;
											$the_matrix['Not Identified-mK' . $this_broken_key[1]][$tmvKey] += $tmvValue;
										}
										//$refactor[$tmKey] = $the_matrix[$tmKey];
										$refactor = true;
										unset($the_matrix[$tmKey]);
									}
								}
							}
							//reconfigure Not Identified percentages based on conglomerate category totals
							if($refactor) {
								foreach($broken_keys as $bkValue) {
									foreach($the_matrix['Not Identified-mK' . $bkValue] as $niKey => $niValue) {
										if(preg_match('/^\d{1,3}_$/',$niKey) && (int)$niValue > 0) {
											$niaKey = explode('_',$niKey);
											$the_matrix['Not Identified-mK' . $bkValue][$niaKey[0]] = opin_round($niValue / $the_matrix['Not Identified-mK' . $bkValue][0] * 100);
											//echo 'niValue: ' . $niValue . ' total: ' . $the_matrix['Not Identified-mK' . $broken_key[1]][0] . ' = ' . $the_matrix['Not Identified-mK' . $broken_key[1]][$niaKey[0]] . '<br />';
										}
									}
								}
							}
						}

						foreach($the_matrix as $tmKey => $tmValue) {
							if(stristr($tmKey,'-mK' . $msKey)) {//this is a demographic question
								for($i=0;$i<count($tmValue) -1;$i++) {
									switch($i) {
										case 0:
											$row_eligible = false;
											if($this_sig_thresh > 0) {
												//if the column total is greater than or equal to the significant threshold percentage of the overall total, the row is eligible for colorization
												if((int)$tmValue[0] >= $this_sig_thresh) $row_eligible = true;
											}
											$secondary_output .= '<tr><td class="opin-numeric">' . $tmValue[0] . '</td>' . PHP_EOL;
											break;
										default:
											if(isset($tmValue[$i])) {
												$deciding_value = opin_round($tmValue[$i],true);
												$viewed_value = $deciding_value . '%';
												if($row_eligible) {//row total meets or exceeds significant threshold percentage of overall total
													//decide if the cell gets + or - colorization or no colorization
													if($deciding_value >= ($column_thresh[$i] + (int)$colorization_threshold)) {//color above
														$viewed_value = '<div class="opin-table-text">' . $deciding_value . '%</div><div class="opin-table-color" style="background-color:' . $threshold_color_above . '"></div>';
													} else if($deciding_value <= ($column_thresh[$i] - (int)$colorization_threshold)) {//color below
														$viewed_value = '<div class="opin-table-text">' . $deciding_value . '%</div><div class="opin-table-color" style="background-color:' . $threshold_color_below . '"></div>';
													}
													//else no color
												}
												$secondary_output .= '<td class="opin-numeric">' . $viewed_value . '</td>' . PHP_EOL;
											}
											break;
									}
								}
								//make category ($secKey) lowercase dashed
								$my_cat = str_replace('-mK' . $msKey,'',$tmKey);
								$this_cat = strtolower($my_cat);
								$this_cat = str_replace(' ','--',$this_cat);
								$this_cat = preg_replace('/[^a-z0-9_-]+/i','',$this_cat);
								$secondary_output .= "<td id='$this_cat' class='categorical'>$my_cat</td>" . PHP_EOL;
								$this_ao_perc = 0;
								$this_pol_perc = 0;
								$this_mpc_perc = 0;
								$this_ao = (int)$tmValue['abstain'] + (int)$tmValue['object'];//abstain + object
								$this_total = (int)$tmValue[0];
								$sec_pol_button = '';
								if($this_total > 0) {
									$this_pol_perc = 100;
									if($this_ao > 0) {
										$this_ao_perc = opin_round(($this_ao/$this_total) * 100);
										$this_pol_perc = 100 - $this_ao_perc;
									}
									$this_yes = (int)$tmValue['positive'];
									$this_neg = (int)$tmValue['negative'];
									$this_neu = (int)$tmValue['neutral'];
									$this_mpc_total = $this_total - $this_ao - $this_neu;
									if($this_yes > 0 && $this_mpc_total > 0) {
										$this_mpc_perc = opin_round(($this_yes/$this_mpc_total) * 100);
									}
									//if all answers are neutral, the mpc rating is 50%
									if($this_yes == 0 && $this_neg == 0 && $this_neu > 0) $this_mpc_perc = 50;
									if($significant_threshold < 1) {
										if($this_total > 0) {
											$pat_res[$tmKey][] = $this_pol_perc;
										}
									} else {
										if($row_eligible) $pat_res[$tmKey][] = $this_pol_perc;
									}
									$sec_pol_button = '<input type="button" value="+" id="button-' . $msKey . '-' . $mKey . '-' . $this_cat . '" onclick="generate_pol(\'' . $this_cat . '\',' . $msKey . ',' . $mKey . ',' . $this_pol_perc . ',' . $this_mpc_perc . ',' . $this_ao_perc . ')" />';
								}
								$secondary_output .= "<td class='pc-rating'>( $this_pol_perc% - $this_mpc_perc%)</td>" . PHP_EOL;
								$secondary_output .= "<td class='sec-pol-graph-button'>$sec_pol_button</td></tr>" . PHP_EOL;
							}
						}
						$secondary_output .= "</tbody><tfoot><tr>" . PHP_EOL;
						$secondary_output .= $footer;
						$secondary_output .= "<td>Total</td>" . PHP_EOL;
						$ao_total = $abstain_master + $object_master;
						$polar_perc = 0;
						if($overall_total > 0) $polar_perc = 100;
						$ao_perc = 0;
						$mpc_perc = 0;
						if($overall_total > 0) {
							if($ao_total > 0) {
								$ao_perc = opin_round(($ao_total/$overall_total) * 100);
								$polar_perc = 100 - $ao_perc;
							}
							$mpc_total = $overall_total - $ao_total - $neutral_master;
							if($yes_master > 0 && $mpc_total > 0) {
								$mpc_perc = opin_round(($yes_master/$mpc_total) * 100);
							}
						}
						$secondary_output .= "<td class='opin-table-total-rating'>( $polar_perc% - $mpc_perc%)</td></tr></tfoot></table>" . PHP_EOL;
						$cross_tab .= $title_before . $title_middle . $title_after;//'mK' . $mKey . ' ' .
						//$cross_tab .= $sec_output;
						$cross_tab .= $secondary_output;
						$sec_output = '';
						$cross_tab .= '</table>' . PHP_EOL;
						//css calculations
						$aow = $ao_perc * 5;//ao * 5 (max)471 (min)29
						$aow = min(471,max(29,$aow));
						$conw = (((502 - $aow) * 0.01) * $mpc_perc) + ($aow + 25);//width of entire pol//1% of entire pol//mpc pointer arrow tip//position of left arrow wing tip
						$conw = max(29,$conw);
						$ao_background = '#82232F';
						$ao_color = 'white';
						$ao_box = 'border: 1px solid black;';
						if($ao_total == 0) {
							$ao_background = '#FFFFFF';
							$ao_color = 'black';
							$ao_box = 'box-shadow: inset 0 0 0 1px #000;';
						}
						$bottom_graphic = '
						<style type="text/css">
							#ao' . $mKey . '-' . $msKey . ' {
								width: ' . $aow . 'px;
								' . $ao_box . '
								text-align: left;
								background-color: ' . $ao_background . ';
								color: ' . $ao_color . ';
							}
							#con' . $mKey . '-' . $msKey . ' {
								width: ' . $conw . 'px;
							}
						</style>';
						$bottom_graphic .= '
						<div id="pol-' . $msKey . '-' . $mKey . '-' . 'bargraphs" class="bargraphs">
							<table class="legend">
								<tbody>
									<tr>
										<td>
											<div class="pcmpc-square pcmpc-ao-key"></div>
											Abstain + Object
										</td>
										<td>
											<div class="pcmpc-square pcmpc-pol-key"></div>
											Polarization
										</td>
										<td>
											<div class="pcmpc-con-key"></div>
											<div class="pcmpc-con-border"></div>
											Consensus
										</td>
									</tr>
								</tbody>
							</table>' . PHP_EOL;
						$bottom_graphic .= '<h3 class="pol-label">Total</h3>' . PHP_EOL;
						$bottom_graphic .= '<table class="opinionnaire-bar">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '<td id="ao' . $mKey . '-' . $msKey . '">&nbsp;' . $ao_perc .' %</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-mid"></td>' . PHP_EOL;
						$bottom_graphic .= '<td id="pol' . $mKey . '-' . $msKey . '" class="pol-graph">' . $polar_perc . ' %&nbsp;</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '<table class="pointer">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td id="con' . $mKey . '-' . $msKey . '"></td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-arrow-td">' . PHP_EOL;
						$bottom_graphic .= '<div class="pcmpc-arrow"></div>' . PHP_EOL;
						$bottom_graphic .= $mpc_perc . ' %' . PHP_EOL;
						$bottom_graphic .= '</td>' . PHP_EOL;
						$bottom_graphic .= '<td></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '</div>' . PHP_EOL;
						$cross_tab .= $bottom_graphic;
						$cross_tab .= '<div class="page-break"></div><br />' . PHP_EOL;
					}
				}
			}

			$cross_tab_cd = '';
			foreach($display_cd_q1 as $mKey => $mValue) {
				$cross_tab_cd .= '<div class="prime-question">' . PHP_EOL;
				$cross_tab_cd .= '<h3>' . $mValue['title'] . '</h3>' . PHP_EOL;
				$count_p_r = count($mValue['response']) - 1;//number of prime question header columns
				$count_p_r += 3;//add 3 for TOTAL, CATEGORY, and PC or MPC RATINGtm and row Polarization button hangs outside of table
				$legend = "<tr><th colspan=$count_p_r>" . PHP_EOL;
				$legend_first = reset($kc_prim_legends[$mKey]);
				$legend_count = count($kc_prim_legends[$mKey]);
				$cross_tab_cd_type = '';
				$rating_type = '';
				//we define the question type based on the number of possible responses (excluding abstain and object which may or may not be present but typically are)
				switch($legend_count) {
					case 2:
						$cross_tab_cd_type = 'yesno';
						$rating_type = 'PC';
						break;
					case 5:
						$cross_tab_cd_type = 'likert5';
						$rating_type = 'MPC';
						break;
					case 7:
						$cross_tab_cd_type = 'likert7';
						$rating_type = 'MPC';
						break;
				}
				//create the legend for the crosstab based on the possible responses
				$patterns = array(0=>'/\(/',1=>'/\d{1,}/',2=>'/\)/');
				$replace = array(0=>'',1=>'',2=>'');
				$legend_first = preg_replace($patterns,$replace,$legend_first);
				$legend_text = $legend_first . " ";
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$legend_text .= "($kcplKey) ";
					$final_legend = $kcplValue;
				}

				//finalize legend
				$final_legend = preg_replace($patterns,$replace,$final_legend);
				$legend_text .= " $final_legend";
				$legend .= $legend_text . "</th><th></th></tr>" . PHP_EOL;

				//create the response headers for the crosstab table
				$categories = "<tr><th>TOTAL</th>" . PHP_EOL;
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$categories .= "<th>($kcplKey)</th>" . PHP_EOL;
				}

				//add abstain and/or object to the response headers if they exist for this question
				foreach($kc_prim_distilled[$mKey] as $kpdMKkey => $kpdMKvalue) {
					if($kpdMKkey == 8 || $kpdMKkey == 9) {
						if($kpdMKvalue == 8) $kpdMKvalue = 'Abstain';
						if($kpdMKvalue == 9) $kpdMKvalue = 'Object';
						$categories .= "<th>" . $kpdMKvalue . "</th>" . PHP_EOL;
					}
				}

				//finalize header row of crosstab table
				$categories .= "<th>CATEGORY</th><th>$rating_type RATING&#8482;</th><th></th>" . PHP_EOL;
				$categories .= "</tr></thead><tbody>" . PHP_EOL;
				$sec_output = '';
				$sec_row = '';
				$the_matrix_reloaded = array();//this is the master array that will contain all of the data to be output in a standardized format

				foreach($mValue as $msKey => $msValue) {
					if(is_int($msKey)) {
						$overall_total = $mValue['response']['numeric_total'] - $mValue['response']['Not Identified'];
						$sig_lower_limit = '';
						$int_significant_threshold = (int)$significant_threshold;
						if(!empty($int_significant_threshold) && !empty($overall_total)) {
							$sig_lower_limit = opin_round($significant_threshold * $overall_total * 0.01,true);//the number in the vertical TOTAL column that a category has to be >= to qualify for significance
						}

						// Pulls individual responses from demographic.

						foreach($msValue as $mssKey => $mssValue) {

							switch($mssKey) {
								case 'title':
									$title_middle = '';
									$title_before = '<table id="question-' . $msKey . '-' . $mKey . '-table" class="opin-data';
									$title_after = '"><thead>' . PHP_EOL;
									$title_after .= "<tr style='background-color:#00CCFF;color:#FFFFFF'><th colspan=$count_p_r class='secondary_question'>$mssValue</th><td></td><tr>" . PHP_EOL;
									$title_after .= $legend;
									$title_after .= $categories;
									break;
								case 'response':

									// The response element contains a copy of all of the individual possible responses to be modified.

									$column_total = array();
									$column_count = 0;
									$abstain_master = 0;
									$object_master = 0;
									$neutral_master = 0;
									$yes_master = 0;
									$over_total = 0;
									foreach($mssValue as $secKey => $secValue) {
										$abstain_row = 0;
										$object_row = 0;
										$neutral_row = 0;
										$yes_row = 0;
										$sec_total = array_sum($secValue);
										if($sec_total > 0 || empty($report_0_cats)) {
											$the_matrix_reloaded[$secKey . '-mK' . $msKey][0] = $sec_total;
											$the_matrix_reloaded[$secKey . '-mK' . $msKey]['abstain'] = 0;//$secValue['Abstain'];
											$the_matrix_reloaded[$secKey . '-mK' . $msKey]['object'] = 0;//$secValue['Object'];
											$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] = 0;
											$the_matrix_reloaded[$secKey . '-mK' . $msKey]['neutral'] = 0;
											$the_matrix_reloaded[$secKey . '-mK' . $msKey]['negative'] = 0;
											$matrix_count = 1;
											$sec_thresh_perc  = opin_round(($sec_total / $overall_total) * 100,true);
											$sec_thresh_color = '';
											if($sec_thresh_perc > $int_significant_threshold && !empty($threshold_color_above) && $int_significant_threshold !== 0) {
												$sec_thresh_color = ' style="background-color:' . $threshold_color_above . '"';
												if(empty($title_middle)) $title_middle = ' colorized';
											}
											$over_total += $sec_total;
											$sec_row .= '<tr><td class="opin-numeric">' . $sec_total . '</td>' . PHP_EOL;
											if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
											$column_total[$column_count] += $sec_total;
											foreach($secValue as $primKey => $secSub) {
												$column_count++;
												if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
												$column_total[$column_count] += $secSub;
												$abstain = 0;
												$object = 0;
												if($primKey === 'Abstain' || $primKey == 8 || $primKey == '8') {
													$abstain = $secSub;
													$abstain_master += $secSub;
													$abstain_row += $secSub;
													$the_matrix_reloaded[$secKey . '-mK' . $msKey]['abstain'] += $secSub;
												} elseif($primKey === 'Object' || $primKey == 9 || $primKey == '9') {
													$object = $secSub;
													$object_master += $secSub;
													$object_row += $secSub;
													$the_matrix_reloaded[$secKey . '-mK' . $msKey]['object'] += $secSub;
												}
												switch($cross_tab_cd_type) {
													case 'yesno':
														if($primKey === 'Yes' || $primKey == 1 || $primKey == '1' || $primKey == $questions['value_statement'][$mKey]->options[0]->value) {
															$yes_row += $secSub;
															$yes_master += $secSub;
															$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] += $secSub;
														}
														break;
													case 'likert5':
														//evaluate $primKey against $kc_prim_questions array
														if(isset($kc_prim_distilled_reverse[$mKey][$primKey])) {//by title
															switch($kc_prim_distilled_reverse[$mKey][$primKey]) {
																case '5':
																case '4':
																	$yes_row += $secSub;
																	$yes_master += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	break;
																case '3':
																	$neutral_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '2':
																case '1':
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														} elseif(isset($kc_prim_distilled[$mKey][$primKey])) {//by keycode
															switch($primKey) {
																case '5':
																case '4':
																	$yes_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '3':
																	$neutral_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '2':
																case '1':
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														}
														break;
													case 'likert7':
														//evaluate $primKey against $kc_prim_questions array
														if(isset($kc_prim_distilled_reverse[$mKey][$primKey])) {//by title
															switch($kc_prim_distilled_reverse[$mKey][$primKey]) {
																case '7':
																case '6':
																case '5':
																	$yes_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '4':
																	$neutral_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '3':
																case '2':
																case '1':
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														} else if(isset($kc_prim_distilled[$mKey][$primKey])) {//by keycode
															switch($primKey) {
																case '7':
																case '6':
																case '5':
																	$yes_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['positive'] += $secSub;
																	$yes_master += $secSub;
																	break;
																case '4':
																	$neutral_row += $secSub;
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['neutral'] += $secSub;
																	$neutral_master += $secSub;
																	break;
																case '3':
																case '2':
																case '1':
																	$the_matrix_reloaded[$secKey . '-mK' . $msKey]['negative'] += $secSub;
																	break;
															}
														}
														break;
												}
												$sec_perc = 0;
												if($sec_total != 0 && $secSub != 0) {
													$sec_perc_raw = ($secSub/$sec_total) * 100;
													$sec_perc = opin_round($sec_perc_raw);
												}
												$td_thresh_color = '';
												if(!empty($sec_thresh_color) && $colorization_threshold !== '') {
													if($sec_perc < $colorization_threshold) {
														$td_thresh_color = ' style="background-color:' . $threshold_color_below . '"';
													} else {
														$td_thresh_color = ' style="background-color:' . $threshold_color_above . '"';
													}
												}
												$the_matrix_reloaded[$secKey . '-mK' . $msKey][$matrix_count] = $sec_perc;
												//this would give us a count by value statement option
												$the_matrix_reloaded[$secKey . '-mK' . $msKey][$matrix_count . '_'] = $secSub;
												$matrix_count++;
												$sec_row .= '<td class="opin-numeric"><div class="opin-table-text">' . $sec_perc . '%</div><div class="opin-table-color"' . $td_thresh_color . '></div></td>' . PHP_EOL;
											}



											if(isset($cd_matrix)) {
												foreach($cd_matrix as $name => $cd) {
													$the_matrix_reloaded[$name] = $cd;
												}
											}
											//make category ($secKey) lowercase dashed
											$this_cat = strtolower($secKey);
											$this_cat = str_replace(' ','--',$secKey);
											$this_cat = preg_replace('/[^a-z0-9_-]+/i','',$this_cat);
											$sec_row .= "<td id='$this_cat' class='categorical'>$secKey</td>" . PHP_EOL;
											$sec_ao_total = $abstain_row + $object_row;
											$sec_ao_perc  = 0;
											$sec_pol_perc = 0;
											if($sec_total > 0) $sec_pol_perc = 100;
											$sec_mpc_perc = 0;
											if($overall_total > 0) {
												if($sec_ao_total > 0) {
													$sec_ao_perc = opin_round(($sec_ao_total/$sec_total) * 100);
													$sec_pol_perc = 100 - $sec_ao_perc;
												}
												if($sec_total > 0) {
													$sec_mpc_total = $sec_total - $sec_ao_total - $neutral_row;
													if($yes_row > 0 && $sec_mpc_total > 0) {
														$sec_mpc_perc = opin_round(($yes_row/$sec_mpc_total) * 100);
													}
												}
											}
											//if the cateogry response total is 0, we don't include this category in the participatory resistance graphic
											if($sec_total > 0) {
												//if the user set a significant threshold and this category does not meet or exceed that threshold, we don't include it in the participatory resistance graphic
												if($int_significant_threshold > 0 && $sec_thresh_perc > $significant_threshold) {
													$con_totals[$secKey][] = $sec_mpc_perc;//consensus
												} else {
													$con_totals[$secKey][] = $sec_mpc_perc;//consensus
												}
											}
											$sec_row .= "<td class='pc-rating'>( $sec_pol_perc% - $sec_mpc_perc%)</td>" . PHP_EOL;
											$sec_pol_button = '';
											if($sec_total > 0) {
												//pass to onclick params: category, demo question id, value statement question id, poloarization percentage, consensus percentage, abstain/object percentage
												//targets table id: question-$demo question id-$value statement question id-table
												$sec_pol_button = '<input type="button" value="+" id="button-' . $msKey . '-' . $mKey . '-' . $this_cat . '" onclick="generate_pol(\'' . $this_cat . '\',' . $msKey . ',' . $mKey . ',' . $sec_pol_perc . ',' . $sec_mpc_perc . ',' . $sec_ao_perc . ')" />';
											}
											$sec_row .= "<td>$sec_pol_button</td>" . PHP_EOL;
											$sec_row .= "</tr>" . PHP_EOL;
											$column_count = 0;
											if(empty($report_0_cats)) {
												$sec_output .= $sec_row;
											} else if($sec_total > 0) {
												$sec_output .= $sec_row;
											}
										}
										$sec_row = '';
									}
									$sec_output .= "</tbody><tfoot><tr>" . PHP_EOL;
									foreach($column_total as $ctKey => $cTotal) {
										if($ctKey == 0) {
											$sec_output .= "<td class='opin-numeric'>$cTotal</td>" . PHP_EOL;
										} else {
											$cPerc = 0;
											if($cTotal > 0) {
												$cPerc = opin_round(($cTotal/$overall_total) * 100);
											}
											$sec_output .= "<td class='opin-numeric'>$cPerc%</td>" . PHP_EOL;
										}
									}
									$sec_output .= "<td>Total</td>" . PHP_EOL;
									$ao_total = $abstain_master + $object_master;
									$polar_perc = 0;
									if($overall_total > 0) $polar_perc = 100;
									$ao_perc = 0;
									$mpc_perc = 0;
									if($overall_total > 0) {
										if($ao_total > 0) {
											$ao_perc = opin_round(($ao_total/$overall_total) * 100);
											$polar_perc = 100 - $ao_perc;
										}
										$mpc_total = $overall_total - $ao_total - $neutral_master;
										if($yes_master > 0 && $mpc_total > 0) {
											$mpc_perc = opin_round(($yes_master/$mpc_total) * 100);
										}
									}
									$sec_output .= "<td class='opin-table-total-rating'>( $polar_perc% - $mpc_perc%)</td></tr></tfoot>" . PHP_EOL;
									break;
							}
						}
						//This is where we start the new table output
						$secondary_output = '';
						$footer = '';
						$this_sig_thresh = 0;
						$column_thresh = array();
						$column_thresh[0] = 0;
						foreach($column_total as $ctKey => $cTotal) {
							if($ctKey == 0) {
								$footer .= "<td class='opin-numeric'>$cTotal</td>" . PHP_EOL;
								if($significant_threshold > 0) {
									//$sig_thresh * 0.01 [because sig_thresh is a percent] * overall_total tells us how much a row has to be to qualify in terms of raw count
									$this_sig_thresh = opin_round($overall_total * ($significant_threshold * 0.01),true);
									$column_thresh[0] = $this_sig_thresh;
								}
							} else {
								$cPerc = 0;
								if($cTotal > 0) {
									$cPerc = opin_round(($cTotal/$overall_total) * 100);
								}
								$column_thresh[$ctKey] = 0;
								if($colorization_threshold > 0) {
									if($cTotal > 0) {
										$column_thresh[$ctKey] = $cPerc;
									}
								}
								$footer .= "<td class='opin-numeric'>$cPerc%</td>" . PHP_EOL;
							}
						}
						if($this_sig_thresh) {
							$refactor = false;
							reset($the_matrix_reloaded);
							$broken_keys = array();
							foreach($the_matrix_reloaded as $tmKey => $tmValue) {
								$this_broken_key = explode('-mK',$tmKey);
								$broken_keys[$this_broken_key[1]] = $this_broken_key[1];
								if($tmKey != 'Not Identified-mK' . $this_broken_key[1]) {
									if($tmValue[0] < $this_sig_thresh) {
										if(!isset($the_matrix_reloaded['Not Identified-mK' . $this_broken_key[1]])) $the_matrix_reloaded['Not Identified-mK' . $broken_key[1]] = array();
										foreach($tmValue as $tmvKey => $tmvValue) {
											if(!isset($the_matrix_reloaded['Not Identified-mK' . $this_broken_key[1]][$tmvKey])) $the_matrix_reloaded['Not Identified-mK' . $this_broken_key[1]][$tmvKey] = 0;
											$the_matrix_reloaded['Not Identified-mK' . $this_broken_key[1]][$tmvKey] += $tmvValue;
										}
										//$refactor[$tmKey] = $the_matrix_reloaded[$tmKey];
										$refactor = true;
										unset($the_matrix_reloaded[$tmKey]);
									}
								}
							}
							//reconfigure Not Identified percentages based on conglomerate category totals
							if($refactor) {
								foreach($broken_keys as $bkValue) {
									foreach($the_matrix_reloaded['Not Identified-mK' . $bkValue] as $niKey => $niValue) {
										if(preg_match('/^\d{1,3}_$/',$niKey) && (int)$niValue > 0) {
											$niaKey = explode('_',$niKey);
											$the_matrix_reloaded['Not Identified-mK' . $bkValue][$niaKey[0]] = opin_round($niValue / $the_matrix_reloaded['Not Identified-mK' . $bkValue][0] * 100);
										}
									}
								}
							}
						}

						foreach($the_matrix_reloaded as $tmKey => $tmValue) {
							if(stristr($tmKey,'-mK' . $msKey)) {//this is a demographic question
								for($i=0;$i<count($tmValue) -1;$i++) {
									switch($i) {
										case 0:
											$row_eligible = false;
											if($this_sig_thresh > 0) {
												//if the column total is greater than or equal to the significant threshold percentage of the overall total, the row is eligible for colorization
												if((int)$tmValue[0] >= $this_sig_thresh) $row_eligible = true;
											}
											$secondary_output .= '<tr><td class="opin-numeric">' . $tmValue[0] . '</td>' . PHP_EOL;
											break;
										default:
											if(isset($tmValue[$i])) {
												$deciding_value = opin_round($tmValue[$i],true);
												$viewed_value = $deciding_value . '%';
												if($row_eligible) {//row total meets or exceeds significant threshold percentage of overall total
													//decide if the cell gets + or - colorization or no colorization
													if($deciding_value >= ($column_thresh[$i] + (int)$colorization_threshold)) {//color above
														$viewed_value = '<div class="opin-table-text">' . $deciding_value . '%</div><div class="opin-table-color" style="background-color:' . $threshold_color_above . '"></div>';
													} else if($deciding_value <= ($column_thresh[$i] - (int)$colorization_threshold)) {//color below
														$viewed_value = '<div class="opin-table-text">' . $deciding_value . '%</div><div class="opin-table-color" style="background-color:' . $threshold_color_below . '"></div>';
													}
													//else no color
												}
												$secondary_output .= '<td class="opin-numeric">' . $viewed_value . '</td>' . PHP_EOL;
											}
											break;
									}
								}
								//make category ($secKey) lowercase dashed
								$my_cat = str_replace('-mK' . $msKey,'',$tmKey);
								$this_cat = strtolower($my_cat);
								$this_cat = str_replace(' ','--',$this_cat);
								$this_cat = preg_replace('/[^a-z0-9_-]+/i','',$this_cat);
								$my_cat = preg_replace('/([0-9]+##+)+/i','',$my_cat);
								$secondary_output .= "<td id='$this_cat' class='categorical'>" . $my_cat . "</td>" . PHP_EOL;
								$this_ao_perc = 0;
								$this_pol_perc = 0;
								$this_mpc_perc = 0;
								$this_ao = (int)$tmValue['abstain'] + (int)$tmValue['object'];//abstain + object
								$this_total = (int)$tmValue[0];
								$sec_pol_button = '';
								if($this_total > 0) {
									$this_pol_perc = 100;
									if($this_ao > 0) {
										$this_ao_perc = opin_round(($this_ao/$this_total) * 100);
										$this_pol_perc = 100 - $this_ao_perc;
									}
									$this_yes = (int)$tmValue['positive'];
									$this_neg = (int)$tmValue['negative'];
									$this_neu = (int)$tmValue['neutral'];
									$this_mpc_total = $this_total - $this_ao - $this_neu;
									if($this_yes > 0 && $this_mpc_total > 0) {
										$this_mpc_perc = opin_round(($this_yes/$this_mpc_total) * 100);
									}
									//if all answers are neutral, the mpc rating is 50%
									if($this_yes == 0 && $this_neg == 0 && $this_neu > 0) $this_mpc_perc = 50;
									if($significant_threshold < 1) {
										if($this_total > 0) {
											$pat_res[$tmKey][] = $this_pol_perc;
										}
									} else {
										if($row_eligible) $pat_res[$tmKey][] = $this_pol_perc;
									}
									$sec_pol_button = '<input type="button" value="+" id="button-' . $msKey . '-' . $mKey . '-' . $this_cat . '" onclick="generate_pol(\'' . $this_cat . '\',' . $msKey . ',' . $mKey . ',' . $this_pol_perc . ',' . $this_mpc_perc . ',' . $this_ao_perc . ')" />';
								}
								$secondary_output .= "<td class='pc-rating'>( $this_pol_perc% - $this_mpc_perc%)</td>" . PHP_EOL;
								$secondary_output .= "<td class='sec-pol-graph-button'>$sec_pol_button</td></tr>" . PHP_EOL;
							}
						}
						$secondary_output .= "</tbody><tfoot><tr>" . PHP_EOL;
						$secondary_output .= $footer;
						$secondary_output .= "<td>Total</td>" . PHP_EOL;
						$ao_total = $abstain_master + $object_master;
						$polar_perc = 0;
						if($overall_total > 0) $polar_perc = 100;
						$ao_perc = 0;
						$mpc_perc = 0;
						if($overall_total > 0) {
							if($ao_total > 0) {
								$ao_perc = opin_round(($ao_total/$overall_total) * 100);
								$polar_perc = 100 - $ao_perc;
							}
							$mpc_total = $overall_total - $ao_total - $neutral_master;
							if($yes_master > 0 && $mpc_total > 0) {
								$mpc_perc = opin_round(($yes_master/$mpc_total) * 100);
							}
						}
						$secondary_output .= "<td class='opin-table-total-rating'>( $polar_perc% - $mpc_perc%)</td></tr></tfoot></table>" . PHP_EOL;
						$cross_tab_cd .= $title_before . $title_middle . $title_after;//'mK' . $mKey . ' ' .
						//$cross_tab_cd .= $sec_output;
						$cross_tab_cd .= $secondary_output;
						$sec_output = '';
						$cross_tab_cd .= '</table>' . PHP_EOL;
						//css calculations
						$aow = $ao_perc * 5;//ao * 5 (max)471 (min)29
						$aow = min(471,max(29,$aow));
						$conw = (((502 - $aow) * 0.01) * $mpc_perc) + ($aow + 25);//width of entire pol//1% of entire pol//mpc pointer arrow tip//position of left arrow wing tip
						$conw = max(29,$conw);
						$ao_background = '#82232F';
						$ao_color = 'white';
						$ao_box = 'border: 1px solid black;';
						if($ao_total == 0) {
							$ao_background = '#FFFFFF';
							$ao_color = 'black';
							$ao_box = 'box-shadow: inset 0 0 0 1px #000;';
						}
						$bottom_graphic = '
						<style type="text/css">
							#ao' . $mKey . '-' . $msKey . ' {
								width: ' . $aow . 'px;
								' . $ao_box . '
								text-align: left;
								background-color: ' . $ao_background . ';
								color: ' . $ao_color . ';
							}
							#con' . $mKey . '-' . $msKey . ' {
								width: ' . $conw . 'px;
							}
						</style>';
						$bottom_graphic .= '
						<div id="pol-' . $msKey . '-' . $mKey . '-' . 'bargraphs" class="bargraphs">
						<table class="legend">
							<tbody>
								<tr>
									<td>
										<div class="pcmpc-square pcmpc-ao-key"></div>
										Abstain + Object
									</td>
									<td>
										<div class="pcmpc-square pcmpc-pol-key"></div>
										Polarization
									</td>
									<td>
										<div class="pcmpc-con-key"></div>
										<div class="pcmpc-con-border"></div>
										Consensus
									</td>
								</tr>
							</tbody>
						</table>' . PHP_EOL;
						$bottom_graphic .= '<h3 class="pol-label">Total</h3>' . PHP_EOL;
						$bottom_graphic .= '<table class="opinionnaire-bar">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '<td id="ao' . $mKey . '-' . $msKey . '">&nbsp;' . $ao_perc .' %</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-mid"></td>' . PHP_EOL;
						$bottom_graphic .= '<td id="pol' . $mKey . '-' . $msKey . '" class="pol-graph">' . $polar_perc . ' %&nbsp;</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '<table class="pointer">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td id="con' . $mKey . '-' . $msKey . '"></td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-arrow-td">' . PHP_EOL;
						$bottom_graphic .= '<div class="pcmpc-arrow"></div>' . PHP_EOL;
						$bottom_graphic .= $mpc_perc . ' %' . PHP_EOL;
						$bottom_graphic .= '</td>' . PHP_EOL;
						$bottom_graphic .= '<td></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '</div>' . PHP_EOL;
						$cross_tab_cd .= $bottom_graphic;
						$cross_tab_cd .= '<div class="page-break"></div><br />' . PHP_EOL;
					}
				}
			}
			$count++;
		}
		$cross_tab .= $cross_tab_cd;
		$cross_tab .= '<div id="pr_graphic_top"></div><br /><br />' . PHP_EOL;
		$cross_tab .= '</div>' . PHP_EOL;

		//if display participatory resistance is checked
		if($participatory_resistance == 'display') {

			//if $pat_res (array of pr by category) is not empty (some rows qualified)
			if(!empty($pat_res)) {
				$pat_res_final = array();
				$pat_res_final_inverted = array();
				$pat_res_count_by_cat = array();
				$title_count_extra_space = 3;
				$title_count_strlen = 1;

				//foreach category as label => percentage of 100
				foreach($pat_res as $prKey => $prValue) {
					if(!stristr($prKey,'not identified')) {
						$myPatResTotal = 0;
						for($i=0;$i<count($prValue);$i++) {
							$myPatResTotal += $prValue[$i];
						}
						$res = opin_round($myPatResTotal/$i);
						$new_key = explode('-mK',$prKey);
						$pat_res_final[$prKey] = $res;
						$pat_res_key_map[$prKey] = $new_key[0];
						$pat_res_count_by_cat[$prKey] = $i - 1;
						//$title_count_strlen
					}
				}
				asort($pat_res_final);
				foreach($pat_res_final as $prfKey => $prfValue) {
					$pat_res_final_inverted[$prfKey] = 100 - $prfValue;
				}
				$prhwm = max(max($pat_res_final),max($pat_res_final_inverted));
				$prhwms = 0;

				foreach($pat_res_final as $prfKey => $prfValue) {
					$data2y[] = $prfValue;
					$data1y[] =$pat_res_final_inverted[$prfKey];
					if(isset($the_matrix[$prfKey][0])) {
						$datax[] = graph_text_normalize($pat_res_key_map[$prfKey]) . "\r\n" . '(N=' . $the_matrix[$prfKey][0] . ')';
					} else {
						$datax[] = graph_text_normalize($pat_res_key_map[$prfKey]) . "\r\n" . '(N=' . $the_matrix_reloaded[$prfKey][0] . ')';
					}
					$prhwms = (strlen($pat_res_key_map[$prfKey]) > $prhwms) ? (strlen($pat_res_key_map[$prfKey]) + 5) : $prhwms;
				}
				if($prhwms > 30) $prhwms = 40;
				require_once( plugin_dir_path( __FILE__ ) . 'jpgraph/jpgraph.php');
				require_once( plugin_dir_path( __FILE__ ) . 'jpgraph/jpgraph_bar.php');
				// Size of graph
				$width = 700;//700;
				// $height=500;
				if(count($datax) == 1) {
					$multiplier = 150;
				} else {
					$multiplier = 100;
				}
				$height = count($datax) * $multiplier;

				// Set the basic parameters of the graph
				$graph = new Graph($width,$height,'auto');
				$graph->SetScale('textlin');
				$left_margin = ($prhwms * 8) + 20;

				// Rotate graph 90 degrees and set margin (left,right,top,bottom)
				$graph->Set90AndMargin($left_margin,20,50,30);

				// Nice shadow
				$graph->SetShadow();

				// Setup title
				$graph->title->Set('Participatory Resistance');
				$graph->title->SetFont(FF_DV_SANSSERIF,FS_BOLD,12);
				$graph->subtitle->Set('');//created by Galen Radtke');
				$graph->subtitle->SetFont(FF_DV_SANSSERIF,FS_BOLD,8);

				// Setup X-axis
				$graph->xaxis->SetTickLabels($datax);
				$graph->xaxis->SetFont(FF_DV_SANSSERIF,FS_NORMAL);
				$graph->xaxis->SetLabelAlign('right','center','right');//aHAlign,aVAlign,aParagraphAlign

				// Some extra margin looks nicer
				//$graph->xaxis->SetLabelMargin(10);

				// Add some grace to y-axis so the bars doesn't go
				// all the way to the end of the plot area
				//$graph->yaxis->scale->SetGrace(20);
				//$graph->xaxis->scale->SetGrace(20);

				// We don't want to display Y-axis
				$graph->yaxis->Hide();

				// Create the bar plots
				$b1plot = new BarPlot($data1y);
				$b2plot = new BarPlot($data2y);

				//set the legend
				//$b1plot->SetLegend('Average Resistance');
				//$b2plot->SetLegend('Average Participation');
				//position the legend
				//$graph->legend->SetPos(0.05,0.98,'right','center');

				// Create the grouped bar plot
				$gbplot = new AccBarPlot(array($b1plot,$b2plot));
				//$gbplot->SetXBase(7);

				// ...and add it to the graPH
				$graph->Add($gbplot);

				// set bar plot attributes
				$b1plot->SetFillColor("red");
				$b1plot->SetColor("red");
				$b2plot->SetFillColor("blue");
				$b2plot->SetColor("blue");

				//You can change the width of the bars if you like
				$gbplot->SetWidth(0.25);

				// We want to display the value of each bar at the top
				$b1plot->value->Show();
				$b1plot->value->SetFont(FF_DV_SANSSERIF,FS_BOLD);
				$b1plot->SetValuePos('center');
				$b1plot->value->SetMargin(20);
				$b1plot->value->SetColor('white');
				$b1plot->value->SetFormat('%01.0f%%');//prints the percentage with no decimals as they are currently rounded numbers
				$b2plot->value->Show();
				$b2plot->value->SetFont(FF_DV_SANSSERIF,FS_BOLD);
				$b2plot->SetValuePos('center');
				$b2plot->value->SetColor('white');
				$b2plot->value->SetFormat('%01.0f%%');//prints the percentage with no decimals as they are currently rounded numbers

				$graph->yaxis->title->SetFont(FF_DV_SANSSERIF,FS_NORMAL);
				$graph->xaxis->title->SetFont(FF_DV_SANSSERIF,FS_NORMAL);

				// .. and stroke the graph
				$graphKey = 'graph_' . md5(time());
				$graph->Stroke(plugin_dir_path( __FILE__ ) . 'out/' . $graphKey . '.png');
				$cross_tab .= '<br /><br /><br /><br /><div class="above-pr-graph">' . PHP_EOL;
				if($significant_threshold > 0) {
					$cross_tab .= '<div class="pr-graph-info-outer active"><div class="pr-graph-info">Showing only categories with significance of ' . $significant_threshold . '% or higher from a total of ' . $overall_total . ' responses</div></div>' . PHP_EOL;
				} else {
					$cross_tab .= '<div class="pr-graph-info-outer inactive"><div class="pr-graph-info">Showing only categories with significance of 0% or higher from a total of ' . $overall_total . ' responses</div></div>' . PHP_EOL;
				}
				$cross_tab .= '<div class="pr-graph-above-middle-spacer"></div>' . PHP_EOL;
				$cross_tab .= '<div class="pr-graph-legend"><img src="' . $plugindir . '/assets/red-blue-legend.jpg" alt="Legend" /></div>' . PHP_EOL;
				$cross_tab .= '<div class="pr-graph-credentials">This chart is the creation of <a href="http://twitter.com/GalenRadtke" target="_new">Mr. Galen Radtke</a>. <a href="mailto:galenradtke@gmail.com">Inquiries welcome</a>.</div>' . PHP_EOL;
				$cross_tab .= '</div>' . PHP_EOL;
				$cross_tab .= '<div id="pr_graphic" style="text-align:left;" width="700px"><img src="' . plugins_url() . '/' .str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'out/' . $graphKey . '.png" alt="'.__('Participatory Resistance') . '" style="width:700px;" />' . PHP_EOL;
				$cross_tab .= '</div>';
				$cross_tab .= '<br />';
			} else {
				$cross_tab .= '<div class="pr-graph-empty"><h3>No Categories qualified for the Participatory Resistance graph.</h3></div>' . PHP_EOL;
			}
		}
		$cross_tab .= '<br />';
		return $cross_tab;
	}

	//create cross tab report
	function generate_report_print(
		$api_selected,$survey_id,
		$survey_title,$value_statement_question_array,
		$demographic_question_array,
		$significant_threshold,
		$colorization_threshold,
		$threshold_color_above,
		$threshold_color_below,
		$user_survey_title,
		$participatory_resistance
	) {
		$color_above = (!empty($threshold_color_above)) ? $threshold_color_above : '#ffffff';
		$color_below = (!empty($threshold_color_below)) ? $threshold_color_below : '#ffffff';
		$display_prim_q = array();
		$display_sec_q = array();
		if(!isset($opin_credentials)) {
			$opin_credentials = '';
		}
		if(isset($survey_id)) {
			//psuedo-raw selected questions
			$questions = retrieve_survey_questions($survey_id,$opin_credentials,$value_statement_question_array,$demographic_question_array);
			//raw survey responses
			$responses = retrieve_survey($survey_id,$opin_credentials);
		}
		if(empty($questions) || $questions['result_ok'] != 1 || $questions['total_count'] < 1) return '<h3>Report not found.</h3>' . PHP_EOL;
		if(empty($responses) || $responses->result_ok != 1 || $responses->total_count < 1) return '<h3>Report not found.</h3>' . PHP_EOL;
		$alt_prim_questions = array();
		$kc_prim_questions = array();
		$kc_prim_legends = array();
		$alt_sec_questions = array();
		$kc_sec_questions = array();
		$abstain_object_keycodes = array(8,9);
		//load css for cross-tab tables
		$plugindir = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
		wp_enqueue_style('opinionnaire-tables-css', $plugindir . '/css/tables.css');
		$cross_tab = '';
		//build the skeleton array to house the totals
		foreach($questions as $qType => $qArray) {
			switch($qType) {
				case 'value_statement':
					foreach($qArray as $qsKey => $qsValue) {
						$display_prim_q[$qsKey]['title'] = $qsValue->title->English;
						$display_prim_q[$qsKey]['response']['numeric_total'] = 0;
						$answer_count = 0;
						foreach($qsValue->options as $qsoKey => $qsoValue) {
							$prim_options[$qsKey][$qsoValue->value] = 0;
							$display_prim_q[$qsKey]['response'][$qsoValue->value] = 0;
							$alt_prim_questions[$qsoValue->title->English] = $qsoValue->value;
							$kc_prim_questions[$qsKey][$answer_count]['title'] = $qsoValue->title->English;
							$kc_prim_questions[$qsKey][$answer_count]['value'] = $qsoValue->value;
							$kc_prim_questions[$qsKey][$answer_count]['keycode'] = $qsoValue->properties->keycodes[0];
							$answer_count++;
							//build legend scale arrays for value_statement questions
							if(!in_array($qsoValue->properties->keycodes[0],$abstain_object_keycodes)) {
								$kc_prim_legends[$qsKey][$qsoValue->properties->keycodes[0]] = $qsoValue->title->English;
							}
						}
					}
					break;
				case 'demographic':
					foreach($qArray as $qsKey => $qsValue) {
						$display_sec_q[$qsKey]['title'] = $qsValue->title->English;
						$answer_count = 0;
						foreach($qsValue->options as $qsoKey => $qsoValue) {
							$display_sec_q[$qsKey]['response'][$qsoValue->value] = array();
							$alt_sec_questions[$qsoValue->title->English] = $qsoValue->value;
							$kc_sec_questions[$qsKey][$answer_count]['title'] = $qsoValue->title->English;
							$kc_sec_questions[$qsKey][$answer_count]['value'] = $qsoValue->value;
							$kc_sec_questions[$qsKey][$answer_count]['keycode'] = $qsoValue->properties->keycodes[0];
							$answer_count++;
						}
					}
					break;
			}
		}
		foreach($display_prim_q as $dpqKey => $dpqValue) {
			foreach($display_sec_q as $dsqKey => $dsqValue) {
				$display_prim_q[$dpqKey][$dsqKey] = $dsqValue;
				foreach($display_prim_q[$dpqKey][$dsqKey]['response'] as $subKey => $subValue) {
					$display_prim_q[$dpqKey][$dsqKey]['response'][$subKey] = $prim_options[$dpqKey];
				}
			}
		}
		$kc_prim_distilled = array();
		$kc_prim_distilled_reverse = array();
		foreach($kc_prim_questions as $kpqKey => $kpqValue) {
			foreach($kpqValue as $kpqvkey => $kpqvValue) {
				$kc_prim_distilled[$kpqKey][$kpqvValue['keycode']] = $kpqvValue['title'];
				$kc_prim_distilled_reverse[$kpqKey][$kpqvValue['title']] = $kpqvValue['keycode'];
			}
		}
		$kc_sec_distilled = array();
		$kc_sec_distilled_reverse = array();
		foreach($kc_sec_questions as $ksqKey => $ksqValue) {
			foreach($ksqValue as $ksqvKey => $ksqvValue) {
				$kc_sec_distilled[$ksqKey][$ksqvValue['keycode']] = $ksqvValue['title'];
				$kc_sec_distilled_reverse[$ksqKey][$ksqvValue['title']] = $ksqvValue['keycode'];
			}
		}
		if(!empty($display_prim_q)) {
			$cross_tab .= '<div class="printable">' . PHP_EOL;
			$cross_tab .= "<h1>$user_survey_title</h1>" . PHP_EOL;
			$cross_tab .= "<h2>$survey_title</h2>" . PHP_EOL;
			//iterate over the survey responses and plug the numbers in the skeleton
			foreach($responses->data as $rKey => $rValue) {
				foreach($value_statement_question_array as $pValue) {
					//put the question number into the form the response object can match to "[question($question_number)]"
					$p_q = '[question(' . $pValue . ')]';
					//if($rValue->$p_q != 'Not Identified') {
						if(isset($display_prim_q[$pValue]['response'][$rValue->$p_q]) || isset($display_prim_q[$pValue]['response'][$alt_prim_questions[$rValue->$p_q]])) {
							$this_answer = $rValue->$p_q;
							if(isset($display_prim_q[$pValue]['response'][$alt_prim_questions[$rValue->$p_q]])) $this_answer = $alt_prim_questions[$rValue->$p_q];
							$display_prim_q[$pValue]['response'][$this_answer]++;
							$display_prim_q[$pValue]['response']['numeric_total']++;
							foreach($demographic_question_array as $sValue) {
								//put the question number  into the form the response object can match to "[question($question_number)]"
								$s_q = '[question(' . $sValue . ')]';
								$found = false;
								foreach($display_prim_q[$pValue][$sValue]['response'] as $ssubKey => $ssubValue) {
									if($rValue->$s_q == $ssubKey || $alt_sec_questions[$rValue->$s_q] == $ssubKey) {
										$this_sub_answer = $rValue->$s_q;
										if($alt_sec_questions[$rValue->$s_q] == $ssubKey) $this_sub_answer = $alt_sec_questions[$rValue->$s_q];
										$display_prim_q[$pValue][$sValue]['response'][$this_sub_answer][$this_answer]++;
										$found = true;
									}
								}
							}
						}
					//}
				}
			}
			foreach($display_prim_q as $mKey => $mValue) {
				$cross_tab .= '<div class="prime-question">' . PHP_EOL;
				$cross_tab .= '<h3>' . $mValue['title'] . '</h3>' . PHP_EOL;
				$count_p_r = count($mValue['response']) - 1;//number of prime question header columns
				$count_p_r += 3;//add 3 for TOTAL, CATEGORY, and PC or MPC RATINGtm
				$legend = "<tr><th colspan=$count_p_r>" . PHP_EOL;
				$legend_first = reset($kc_prim_legends[$mKey]);
				$legend_count = count($kc_prim_legends[$mKey]);
				$cross_tab_type = '';
				$rating_type = '';
				switch($legend_count) {
					case 2:
						$cross_tab_type = 'yesno';
						$rating_type = 'PC';
						break;
					case 5:
						$cross_tab_type = 'likert5';
						$rating_type = 'MPC';
						break;
					case 7:
						$cross_tab_type = 'likert7';
						$rating_type = 'MPC';
						break;
				}
				$patterns = array(0=>'/ \(/',1=>'/\d{1,}/',2=>'/\)/');
				$replace = array(0=>'',1=>'',2=>'');
				$legend_first = preg_replace($patterns,$replace,$legend_first);
				$legend_text = $legend_first . " ";
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$legend_text .= "($kcplKey) ";
					$final_legend = $kcplValue;
				}
				$final_legend = preg_replace($patterns,$replace,$final_legend);
				$legend_text .= " $final_legend";
				$legend .= $legend_text . "</th></tr>" . PHP_EOL;
				$categories = "<tr><th>TOTAL</th>" . PHP_EOL;
				foreach($kc_prim_legends[$mKey] as $kcplKey => $kcplValue) {
					$categories .= "<th>($kcplKey)</th>" . PHP_EOL;
				}
				$categories .= "<th>ABSTAIN</th>" . PHP_EOL;
				$categories .= "<th>OBJECT</th>" . PHP_EOL;
				$categories .= "<th>CATEGORY</th><th>$rating_type RATING&#8482;</th>" . PHP_EOL;
				$categories .= "</tr></thead><tbody>" . PHP_EOL;
				$sec_output = '';
				foreach($mValue as $msKey => $msValue) {
					if(in_array($msKey,$demographic_question_array)) {
						$overall_total = $mValue['response']['numeric_total'] - $mValue['response']['Not Identified'];
						foreach($msValue as $mssKey => $mssValue) {
							switch($mssKey) {
								case 'title':
									$title = '<table id="question-' . $msKey . '-' . $mKey . '-table" class="opin-data"><thead>' . PHP_EOL;
									$title .= $legend;
									$title .= "<tr style='background-color:#00CCFF;color:#FFFFFF'><th colspan=$count_p_r class='secondary_question'>$mssValue</th><tr>" . PHP_EOL;
									$title .= $categories;
									break;
								case 'response':
									$column_total = array();
									$column_count = 0;
									$abstain_master = 0;
									$object_master = 0;
									$neutral_master = 0;
									$yes_master = 0;
									$over_total = 0;
									foreach($mssValue as $secKey => $secValue) {
										$abstain_row = 0;
										$object_row = 0;
										$neutral_row = 0;
										$yes_row = 0;
										$sec_total = array_sum($secValue);
										$over_total += $sec_total;
										$sec_row .= "<tr><td class='opin-numeric'>$sec_total</td>" . PHP_EOL;
										if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
										$column_total[$column_count] += $sec_total;
										foreach($secValue as $primKey => $secSub) {
											$column_count++;
											if(!isset($column_total[$column_count])) $column_total[$column_count] = 0;
											$column_total[$column_count] += $secSub;
											$abstain = 0;
											$object = 0;
											if($primKey === 'Abstain' || $primKey == 8 || $primKey == '8') {
												$abstain = $secSub;
												$abstain_master += $secSub;
												$abstain_row += $secSub;
											} elseif($primKey === 'Object' || $primKey == 9 || $primKey == '9') {
												$object = $secSub;
												$object_master += $secSub;
												$object_row += $secSub;
											}
											switch($cross_tab_type) {
												case 'yesno':
													if($primKey === 'Yes' || $primKey == 1 || $primKey == '1') {
														$yes_row += $secSub;
														$yes_master += $secSub;
													}
													break;
												case 'likert5':
												case 'likert7':
													//evaluate $primKey against $kc_prim_questions array
													if(isset($kc_prim_distilled_reverse[$mKey][$primKey])) {//by title
														switch($primKey) {
															case 'Strongly agree':
															case 'Moderately agree':
															case 'Agree':
																$yes_row += $secSub;
																$yes_master += $secSub;
																break;
															case 'Neutral':
																$neutral_row += $secSub;
																$neutral_master += $secSub;
																break;
														}
													} else if(isset($kc_prim_distilled[$mKey][$primKey])) {//by keycode
														switch($kc_prim_distilled[$mKey][$primKey]) {
															case 'Strongly agree':
															case 'Moderately agree':
															case 'Agree':
																$yes_row += $secSub;
																$yes_master += $secSub;
																break;
															case 'Neutral':
																$neutral_row += $secSub;
																$neutral_master += $secSub;
																break;
														}
													}
													break;
											}

											$sec_perc = 0;
											if($sec_total != 0 && $secSub != 0) {
												$sec_perc = opin_round(($secSub/$sec_total) * 100);
											}
											$sec_row .= "<td class='opin-numeric'>$sec_perc%</td>" . PHP_EOL;
										}
										$sec_row .= "<td>$secKey</td>" . PHP_EOL;
										$sec_ao_total = $abstain_row + $object_row;
										$sec_ao_perc = 100;
										$sec_mpc_perc = 0;
										if($overall_total > 0) {
											if($sec_ao_total > 0) {
												$sec_ao_perc_round = opin_round(($sec_ao_total/$sec_total) * 100);
												//echo 'sec_ao_per_round: ' . $sec_ao_perc_round . '<br />';
												$sec_ao_perc = 100 - $sec_ao_perc_round;
												//echo 'sec_ao_per: ' . $sec_ao_perc . '<br />';
											}
											if($sec_total > 0) {
												$sec_mpc_total = $sec_total - $sec_ao_total - $neutral_row;
												//echo 'sec_mpc_total: ' . $sec_mpc_total . '<br />';
												//echo 'yes_row: ' . $yes_row . '<br />';
												if($yes_row > 0 && $sec_mpc_total > 0) {
													$sec_mpc_perc = opin_round(($yes_row/$sec_mpc_total) * 100);
													//echo 'sec_mpc_perc: ' . $sec_mpc_perc . '<br />';
												}
											}
										}
										$sec_row .= "<td>( $sec_ao_perc% - $sec_mpc_perc%)</td>" . PHP_EOL;
										$sec_row .= "</tr>" . PHP_EOL;
										$column_count = 0;
									}
									$sec_row .= "</tbody><tfoot><tr>" . PHP_EOL;
									foreach($column_total as $ctKey => $cTotal) {
										if($ctKey == 0) {
											$sec_row .= "<td class='opin-numeric'>$cTotal</td>" . PHP_EOL;
										} else {
											$cPerc = 0;
											if($cTotal > 0) {
												$cPerc = opin_round(($cTotal/$overall_total) * 100);
											}
											$sec_row .= "<td class='opin-numeric'>$cPerc%</td>" . PHP_EOL;
										}
									}
									$sec_row .= "<td>Total</td>" . PHP_EOL;
									$ao_total = $abstain_master + $object_master;
									$polar_perc = 0;
									$ao_perc = 100;
									$mpc_perc = 0;
									if($overall_total > 0) {
										if($ao_total > 0) {
											$ao_perc_round = opin_round(($ao_total/$overall_total) * 100);
											$ao_perc = $ao_perc_round;
											$polar_perc = 100 - $ao_perc_round;
										}
										$mpc_total = $overall_total - $ao_total - $neutral_master;
										if($yes_master > 0 && $mpc_total > 0) {
											$mpc_perc = opin_round(($yes_master/$mpc_total) * 100);
										}
									}
									$sec_row .= "<td>( $polar_perc% - $mpc_perc%)</td></tr></tfoot>" . PHP_EOL;
									break;
							}
						}
						$cross_tab .= $title;
						$cross_tab .= $sec_row;
						$sec_row = '';
						$cross_tab .= '</table>' . PHP_EOL;
						//css calculations
						$aow = $ao_perc * 5;//ao * 5 (max)471 (min)29
						$aow = min(471,max(29,$aow));
						$conw = (((502 - $aow) * 0.01) * $mpc_perc) + ($aow + 25);//width of entire pol//1% of entire pol//mpc pointer arrow tip//position of left arrow wing tip
						$conw = max(29,$conw);
						$bottom_graphic = '
						<style type="text/css">
							#ao' . $mKey . '-' . $msKey . ' {
								width: ' . $aow . 'px;
								border: 1px solid black;
								text-align: left;
								background-color: rgb(130,35,47);
								color: white;
							}
							#con' . $mKey . '-' . $msKey . ' {
								width: ' . $conw . 'px;
							}
						</style>';
						$bottom_graphic .= '
						<div id="pol-' . $msKey . '-' . $mKey . '-' . 'bargraphs">
							<table class="legend">
								<tbody>
									<tr>
										<td>
											<div class="pcmpc-square pcmpc-ao-key"></div>
											Abstain + Object
										</td>
										<td>
											<div class="pcmpc-square pcmpc-pol-key"></div>
											Polarization
										</td>
										<td>
											<div class="pcmpc-con-key"></div>
											<div class="pcmpc-con-border"></div>
											Consensus
										</td>
									</tr>
								</tbody>
							</table>' . PHP_EOL;
						$bottom_graphic .= '<table class="opinionnaire-bar">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '<td id="ao' . $mKey . '-' . $msKey . '">' . $ao_perc .' %</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-mid"></td>' . PHP_EOL;
						$pol_style = '';
						if($polar_perc == 0) {
							$pol_style = ' style="box-shadow: 0px 0px 0px 1px rgb(0, 0, 0) inset; background: rgb(255, 255, 255) none repeat scroll 0% 0%; color: black;"';
						}
						$bottom_graphic .= '<td id="pol' . $mKey . '-' . $msKey . '"' . $pol_style . '>' . $polar_perc . ' %</td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-bar-spacer-side"></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '<table class="pointer">' . PHP_EOL;
						$bottom_graphic .= '<tr>' . PHP_EOL;
						$bottom_graphic .= '<td id="con' . $mKey . '-' . $msKey . '"></td>' . PHP_EOL;
						$bottom_graphic .= '<td class="pcmpc-arrow-td">' . PHP_EOL;
						$bottom_graphic .= '<div class="pcmpc-arrow"></div>' . PHP_EOL;
						$bottom_graphic .= $mpc_perc . ' %' . PHP_EOL;
						$bottom_graphic .= '</td>' . PHP_EOL;
						$bottom_graphic .= '<td></td>' . PHP_EOL;
						$bottom_graphic .= '</tr>' . PHP_EOL;
						$bottom_graphic .= '</table>' . PHP_EOL;
						$bottom_graphic .= '</div>' . PHP_EOL;
						$cross_tab .= $bottom_graphic;
						$cross_tab .= '<div class="page-break"></div>' . PHP_EOL;
					}
				}
			}
			$count++;
		}
		$cross_tab .= '</div>' . PHP_EOL;
		return $cross_tab;
	}

	//forms cURL $url, takes given front and back and per_page and inserts credentials then iterates and concatenates
	//paged responses into one multi-dimensional object
	function build_get_request($url_front,$url_back,$opin_credentials,$per_page) {
		global $wpdb;
		if($per_page > 500) $per_page = 500;//api return limit
		if($per_page < 50) $per_page = 50;//just to keep things honest
		$user = $opin_credentials[0]->source_user; //Email address used to log in
		$pass = rtrim(opin_encrypt_decrypt('decrypt',$opin_credentials[0]->source_pass,$opin_credentials[0]->source_key,$opin_credentials[0]->source_iv));
		$pass = urlencode($pass);
		$url = $url_front . "api_token={$user}&api_token_secret={$pass}" . $url_back;
		$output = execute_get_request($url . "&page=1&resultsperpage=" . $per_page);

		//The standard return from the API is JSON, decode to php. (It is also possible to request a PSON, PHP object, back. See documentation for more details)
		$output = json_decode($output);
		if($output->total_count > 1) {
			$total_pages = $output->total_pages;

			//if there are more results than the $per_page, iterate and grab the rest (limit is 500)
			if($total_pages > 1) {
				$iterations_plus = (int)$total_pages + 1;
				for($i=2;$i<$iterations_plus;$i++) {
					$additional_output = execute_get_request($url . "&page=" . $i . "&resultsperpage=" . $per_page);
					$additional_output = json_decode($additional_output);
					$datum_count = ($per_page * $i) - $per_page;
					foreach($additional_output->data as $aoKey => $aoValue) {
						$output->data[$datum_count] = $aoValue;
						$datum_count++;
					}
				}
			}
		}
		if($output->result_ok === false) {
			display_survey_unable_to_connect($output->message);
		}
		return $output;
	}

	function execute_get_request($url){
		$output = wp_remote_get($url);
		$output = wp_remote_retrieve_body($output);
		return $output;
	}

	//display cannot connect message
	function display_survey_unable_to_connect($message='') {
		echo '<div class="error">Unable to Connect to API: ' . $message . '</div>' . PHP_EOL;
	}

	//intermediary returns list array of surveys for a given api, possibly needless, we'll see when we incorporate other api's
	function form_populate($opin_credentials=false) {
		$surveys = survey_get_list($opin_credentials);
		return $surveys;
	}

	// gets a list of the surveys from the api and returns them as a list array('id'=>'name (id)')
	function survey_get_list($opin_credentials=false) {
		if($opin_credentials !== false) {
			$url_front = "https://restapi.surveygizmo.com/v4/survey?";
			$url_back = '';
			$surveys = build_get_request($url_front,$url_back,$opin_credentials,500);
			$survey_list = array();
			if(!empty($surveys)) {
				if($surveys->result_ok == 1) {
					foreach($surveys->data as $sKey => $sValue) {
						if($sValue->_type == 'Survey') {
							$survey_list[$sValue->id] = $sValue->title;
						}
					}
				}
				asort($survey_list);
			}
		} else {//use sample data
			$survey_list = array(1581595=>'AIForums Round 4 Opinionnaire® Survey');
		}
		return $survey_list;
	}

	function survey_test_api_call($opin_credentials=false) {
		$result_ok = false;
		if($opin_credentials !== false) {
			$url_front = "https://restapi.surveygizmo.com/v4/survey?";
			$url_back = '';
			$surveys = build_get_request($url_front,$url_back,$opin_credentials,500);
			if(!empty($surveys)) {
				if($surveys->result_ok == 1) {
					$result_ok = true;
				}
			}
		}
		return $result_ok;
	}

	//gets a list of the questions contained in a given survey and returns them as a list array('id'=>'question')
	function retrieve_survey_questions($survey_id,$opin_credentials=false,$value_statement_question_array=array(),$demographic_question_array=array()) {
		$return_questions = array();
		$custom_demo = array();
		if(!empty($survey_id)) {
			if($opin_credentials !== false) {
				$url_front = "https://restapi.surveygizmo.com/v4/survey/{$survey_id}/surveyquestion?";
				$url_back = '';
				$questions = build_get_request($url_front,$url_back,$opin_credentials,500);
			} else {//use sample data
				global $wpdb;
				$sample_data_table_name = $wpdb->prefix . 'opinionnaire_sample_data';
				$results = $wpdb->get_results( "SELECT data FROM $sample_data_table_name WHERE type='questions' LIMIT 1;" );
				$questions = json_decode($results[0]->data);
			}
			$accepted_subtypes = array('radio','menu');
			if(!empty($questions) && $questions->result_ok == 1 && $questions->total_count > 0) {
				if(empty($value_statement_question_array)) {
					$return_questions = build_question_list($questions,$accepted_subtypes);
				} else {
					$return_questions = build_chosen_questions($questions,$accepted_subtypes,$value_statement_question_array,$demographic_question_array);
				}
				if(!empty($return_questions)) {
					$refactored_questions = array();
					foreach($questions->data as $qdKey => $qdValue) {
						$refactored_questions[$qdValue->id] = $qdValue;
					}
					if(!empty($refactored_questions)) {
						//first get all the known (standard) demographic questions
						foreach($return_questions['demographic'] as $rqdKey => $rqdValue) {
							$custom_demo[$rqdKey] = $refactored_questions[$rqdKey];
						}
						//next get the non-standard demographic questions
						foreach($refactored_questions as $rqKey => $rqValue) {
							if(!empty($rqValue->title->English)) {
								if(!isset($custom_demo[$rqKey])) {
									//Survey Gizmo includes a hidden span tag in the title that contains D-# where # is a number
									//presumably some type of display order, but it gets in our way, so we isolate it for title comparison
									$this_title_encoded = htmlspecialchars($rqValue->title->English);
									$this_title_array = explode('&lt;/span&gt;',$this_title_encoded);
									if(!empty($this_title_array[1])) {
										$this_title = strtolower($this_title_array[1]);
										switch($this_title) {
											case (preg_match('/zipcode/',$this_title) ? true : false)://fall through
											case (preg_match('/zip code/',$this_title) ? true : false)://fall through
												$custom_demo[$rqKey] = $rqValue;
												break;
										}
									}
								}
							}
						}
					}
				}
			}
			$return_questions['custom_demo'] = $custom_demo;
		}
		return $return_questions;
	}

	function build_question_list($questions,$accepted_subtypes) {
		$return_questions = array();
		$non_questions_array = array('media','instructions','hidden','table');
		$unable_to_evaluate_questions_array = array('essay');
		$vs_check_array = array('strongly agree','agree','neutral','disagree','strongly disagree','yes','no','abstain','object');
		$demo_check_array = array('not identified');
		foreach($questions->data as $qKey => $qValue) {
			$array_name = 'unknown';
			$disabled = $qValue->properties->disabled;
			if(!$disabled) {
				if($qValue->_type == 'SurveyQuestion' && empty($qValue->properties->hidden) && in_array($qValue->_subtype,$accepted_subtypes)) {
					foreach($qValue->options as $oKey => $oValue) {
						if(!isset($oValue->properties->keycodes[0]) || (empty($oValue->properties->keycodes[0]) && $oValue->properties->keycodes[0] != '0')) {
							$array_name = 'no_keycode';
							continue;
						} elseif($oValue->properties->keycodes[0] > 9) {
							$array_name = 'keycode_out_of_bounds';
							continue;
						} elseif(isset($oValue->value) && !empty($oValue->value)) {
							if(in_array(strtolower($oValue->value),$vs_check_array)) {
								$array_name = 'value_statement';
							} elseif(in_array(strtolower($oValue->value),$demo_check_array)) {
								$array_name = 'demographic';
							}
						}
					}
					if(empty($qValue->options)) $array_name = 'no_keycode';
					$return_questions[$array_name][$qValue->id] = $qValue->title->English;
				} else if(!in_array($qValue->_subtype,$non_questions_array)) {
					$return_questions['wrong_subtype'][$qValue->id] = $qValue->title->English;
				}
			}
		}
		return $return_questions;
	}

	function build_chosen_questions($questions,$accepted_subtypes,$value_statement_question_array,$demographic_question_array) {
		$return_questions = array();
		foreach($questions->data as $qKey => $qValue) {
			if($qValue->_type == 'SurveyQuestion' && empty($qValue->properties->hidden) && in_array($qValue->_subtype,$accepted_subtypes)) {
				if(in_array($qValue->id,$demographic_question_array)) {
					$return_questions['demographic'][$qValue->id] = $qValue;
				} elseif(in_array($qValue->id,$value_statement_question_array)) {
					$return_questions['value_statement'][$qValue->id] = $qValue;
				}
			}
		}
		$return_questions['result_ok'] = $questions->result_ok;
		$return_questions['total_count'] = $questions->total_count;
		return $return_questions;
	}

	//gets the responses for a given survey that are complete and returns them as a multi-dimensional object
	function retrieve_survey($survey_id,$opin_credentials=false) {
		if(isset($survey_id) && !empty($survey_id)) {
			$return = '';
			//Options Filter examples, uncomment to see theese in use
			$status = "&filter[field][1]=status&filter[operator][1]==&filter[value][1]=Complete";//Only show complete responses
			#$datesubmmitted = "&filter[field][0]=datesubmitted&filter[operator][0]=>=&filter[value][0]=2011-02-23+13:23:28";//Submit date greater than 2/23/2011 at 1:23:28 PM
			//Restful API Call URL
			$url_front = "https://restapi.surveygizmo.com/v4/survey/{$survey_id}/surveyresponse?";
			$url_back = "{$status}&resultsperpage=500";
			//GetSurveyResponse Return Example (.debug format):
			//echo $url;
			if($opin_credentials !== false) {
				$output = build_get_request($url_front,$url_back,$opin_credentials,500);
			} else {//use sample data
				global $wpdb;
				$sample_data_table_name = $wpdb->prefix . 'opinionnaire_sample_data';
				$results = $wpdb->get_results( "SELECT data FROM $sample_data_table_name WHERE type='responses' LIMIT 1;" );
				$output = json_decode($results[0]->data);
			}
			//If there are no responses, display an error (Either if the filter is to strong or the given survey does not have responses)
			if($output->total_count < 1) return '<h3>No responses found</h3>' . PHP_EOL;
			return $output;
		}
	}

	/*
	//not yet used
	function generate_report_not_used($id) {
		// Get the file details
		$file = get_post($id);
		// Account details
		$user = 'tom@fdgweb.com'; //Email address used to log in
		$pass = 'Survey10101!'; //Passord used to log in
		// Declare a new SoapClient - This is a class from PHP
		$client = new SoapClient('http://cloud.idrsolutions.com:8080/HTML_Page_Extraction/IDRConversionService?wsdl');
		// Get the data of the file as bytes
		$contents = file_get_contents(wp_get_attachment_url($id));
		// plugin_dir_path(__FILE__) gets the location of the plugin directory
		// Using preg replace to replace the directory separators with the correct type
		// This is where the output will be written to
		$outputdir = preg_replace("[\\/]", DIRECTORY_SEPARATOR, plugin_dir_path(__FILE__)) . "output".DIRECTORY_SEPARATOR. $file->post_title. DIRECTORY_SEPARATOR;
		echo $outputdir;
		if(!file_exists($outputdir)) {
			mkdir($outputdir,0777,true);
		}
		// Declare stlye parameters here - left blank here
		$style_params = array();
		// Set up array for the conversion
		$conversion_params = array(
			"email" => $email,
			"password" =>$password,
			"fileName"=>$file->post_title,
			"dataByteArray"=>$contents,
			"conversionType"=>"html5",
			"conversionParams"=>$style_params,
			"xmlParamsByteArray"=>null,
			"isDebugMode"=>false
		);
		try {
			$output = (array)($client->convert($conversion_params));
			// This method is very improtant as it allows us access to the file system
			WP_Filesystem();
			// Write output as zip
			file_put_contents($outputdir.$file->post_title.".zip", $output);
			// Unzip the file
			$result=unzip_file($outputdir.$file->post_title.".zip", $outputdir);
		} catch(Exception $e) {
			echo $e->getMessage() . "<br/>";
			return;
		}
	}
	*/

	//utility function
	//given haystack and needle, returns portion of string after needle if needle is found in haystack
	//example: $haystack='primary_69',$needle='primary_',returns '69'
	function strstr_after($haystack, $needle, $case_insensitive = false) {
		$strpos = ($case_insensitive) ? 'stripos' : 'strpos';
		$pos = $strpos($haystack, $needle);
		if(is_int($pos)) {
			return substr($haystack, $pos + strlen($needle));
		}
		// Most likely false or null
		return $pos;
	}

	//not currently used, left as reference
	function form_handle_post() {
		$return = array();
		$return['data'] = '';
		if(isset($_REQUEST['survey_id'])) {
			$survey_id_sanitized = sanitize_text_field($_REQUEST['survey_id']);
			$return['questions'] = retrieve_survey_questions($survey_id_sanitized);
		}
		if(empty($return['data'])) $return['data'] = '<h3>Report not found.</h3>' . PHP_EOL;
		return $return;
	}

	/* function to redirect to add credentials when $opin_source is empty
	 * parameters:
	 * opin_source: array containing source variables
	 * source_id: id of source for the given api
	 */
	function opin_redirect_to_add_credentials($opin_source,$api_selected) {
		$message = 1;
		if(!empty($api_selected)) {
			if(!empty($opin_source)) {
				if(!empty($opin_source[0]->id)) {
					if($opin_source[0]->name === $api_selected) {
						return false;
					}
				}
			}
		}
		redirect_to_get_credentials($message);
	}

	/* function to redirect to add credentials when user has no credentials
	 * parameters:
	 * opin_credentials: array containing user credentials
	 * source_id: id of source for the given api
	 */
	function opin_check_credentials($opin_credentials,$api_selected) {
		$message = 1;
		if(!empty($api_selected)) {
			if(!empty($opin_credentials)) {
				if(!empty($opin_credentials[0]->source_user) && !empty($opin_credentials[0]->source_pass)) {
					return false;
				}
			}
		}
		redirect_to_get_credentials($message);
	}

	/* function to redirect to add credentials when user has no credentials
	 * parameters:
	 * opin_credentials: array containing user credentials
	 * source_id: id of source for the given api
	 */
	function redirect_to_get_credentials($message){
		$newLocation = get_admin_url() . 'admin.php?page=manage-survey-source-credentials&message=' . $message;
		header($newLocation);
	}
	/* function to encrypt or decrypt a value
	 * paramters:
	 * $encrypt_decrypt: string encrypt or decrypt
	 * $value: string to encrypt or decrypt
	 * $iv: string to use as initial vector to decrypt and encrypted value
	 */
	function opin_encrypt_decrypt($encrypt_decrypt,$value,$key=false,$iv=false) {
		$encrypt_decrypt_array = array('encrypt','decrypt');
		$crypted = '';
		define('CRYPT_MODE_EBC', 1);
		define('CRYPT_AES_MODE_EBC', 1);
		if(in_array($encrypt_decrypt,$encrypt_decrypt_array) && strlen($value) > 0) {
			$key1 = "secret key half one";
			$key2 = "half two secret key";
			$input = "testing";
			require_once(plugin_dir_path( __FILE__ ) . 'phpseclib/Crypt/Rijndael.php');
			$rijndael = new Crypt_Rijndael(CRYPT_MODE_EBC);
			switch($encrypt_decrypt) {
				case 'encrypt':
					require_once(plugin_dir_path( __FILE__ ) . 'phpseclib/Crypt/AES.php');
					require_once(plugin_dir_path( __FILE__ ) . 'phpseclib/Crypt/Random.php');
					$cipher = new Crypt_AES(CRYPT_AES_MODE_EBC);
					$ks = 256;
					$cipher->setKeyLength($ks);
					$key1 = md5($key1);
					$key2 = md5($key2);
					$key = substr($key1, 0, $ks/2) . substr($key2, (round(strlen($key2) / 2)), $ks/2);
					$key = substr($key . $key1 . $key2 . $key1, 0, $ks);
					$buffer = str_split($key);
					$limit = count($buffer) - 1;
					$end = rand(0,$limit);
					$a = 0;
					while($a < $end) {
						list($usec,$sec) = explode(' ',microtime());
						$seed = ((float)$sec) + ((float)$usec * 100000);
						mt_srand($seed);
						$index = mt_rand(0,$limit);
						$buffer[$index] = strtoupper($buffer[$index]);
						$a++;
					}
					$key = join('',$buffer);
					$iv = $cipher->setIV(crypt_random_string($cipher->getBlockLength() >> 3));
					$rijndael->setKey($key);
					$rijndael->iv = $iv;
					$encrypted = $rijndael->encrypt($value);
					$crypted = array('value'=>$encrypted,'key'=>$key,'iv'=>$iv);
					//$crypted = '{value:"' . $encrypted . '",key:"' . $key . '",iv:"' . $iv .'"}';
					return $crypted;
					break;
				case 'decrypt':
					if($key && $iv) {
						$rijndael->setKey($key);
						$rijndael->iv = $iv;
						$crypted = $rijndael->decrypt($value);
						return $crypted;
					}
					break;
			}
		}
		return false;
	}
	/* function to round a given value
	 * parameters:
	 * value: string or numeric (float,int,etc.)
	 * down: bool, default is false
	 * takes value and rounds
	 * if down is true it returns value minus anything after decimal
	 * else returns value rounded at .0 to .4 rounded down or at .5 to .9 rounded up
	 */
	function opin_round($value,$down=false) {
		if(empty($value)) return 0;
		$str_value = '' . $value;
		$arr_value = explode('.',$str_value);
		if(!isset($arr_value[1]) || empty($arr_value[1]) || $down == true) return (int)$value;
		$dec_value = (int)$arr_value[1][0];//this gives us the first digit of the decimal string
		if($dec_value > 4) return (int)$arr_value[0] + 1;
		return (int) $arr_value[0];
	}
	/* function to limit width of title in participatory resistance graph
	 * parameters:
	 * title: string
	 */
	function graph_text_normalize($title='') {
		if(empty($title)) return $title;
		if(strlen($title) < 31) return $title;
		$space_array = explode(' ',$title);
		//if it is a long string greater than 30 chars, break it up over more than one line
		if(count($space_array) == 1) {
			$new_title = chunk_split($title,30,"\r\n");
		} else {
			//array of words, break them up over more than one line
			$width_limit = 30;
			$new_title = '';
			$temp_title = '';
			$curr_count = 0;
			$arr_count = count($space_array);
			for($i=0;$i<$arr_count;$i++) {
				if($i + 1 != $arr_count) {
					$space_array[$i] .= ' ';
				}
				$temp_title .= $space_array[$i];
				$curr_count += strlen($space_array[$i]);
				if($curr_count >= $width_limit) {
					$new_title .= $temp_title . "\r\n";
					$temp_title = '';
					$curr_count = 0;
				}
				if($i == ($arr_count - 1)) $new_title .= $temp_title;
			}
		}
		if(substr($new_title,-2) == "\r\n") {
			$new_title = substr($new_title,0,-2);
		}
		return $new_title;
	}

	/* function to install opinionnaire
	*/
	function opin_install() {
		global $wpdb;
		//create tables for opinionnaire to store user/pass for various api's/sources
		$main_table_name = $wpdb->prefix . 'opinionnaire_users';
		$source_table_name = $wpdb->prefix . 'opinionnaire_sources';
		$sample_data_table_name = $wpdb->prefix . 'opinionnaire_sample_data';
		$old_main_table_name = $wpdb->prefix . 'custom_survey_reports_users';//v0.1 users table
		$old_source_table_name = $wpdb->prefix . 'custom_surey_reports_sources';//v0.1 sources table
		$old_sample_data_table_name = $wpdb->prefix . 'custom_survey_reports_sample_data';//v0.1 sample data table
		$test_data_table_name = $wpdb->prefix . 'custom_survey_reports_test_data';//old sample data table
		$custom_demo_table_name = $wpdb->prefix . 'opinionnaire_custom_demographics';
		$charset_collate = $wpdb->get_charset_collate();

		$main_sql = "CREATE TABLE $main_table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			source_id bigint(20) NOT NULL,
			source_user varchar(60) NOT NULL,
			source_pass tinyblob NOT NULL,
			source_key tinyblob NULL,
			source_iv tinyblob NULL,
			created datetime NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		//$custom_demo_sql is the table for storing the serialized data for the custom demographics features.
		$custom_demo_sql = "CREATE TABLE IF NOT EXISTS $custom_demo_table_name (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`survey_id` varchar(255) DEFAULT NULL,
			`serial_custom_demographic` mediumblob NOT NULL,
			`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
		) $charset_collate";

		$source_sql = "CREATE TABLE $source_table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			url_base varchar(255) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		//always drop this table to ensure that we only have one set of sample data and it is of the latest variety
		$wpdb->query( "DROP TABLE IF EXISTS $sample_data_table_name;" );

		//drop this table if exists because it is the old sample data table and is no longer used
		$wpdb->query( "DROP TABLE IF EXISTS $test_data_table_name;" );

		$sample_data_sql = "CREATE TABLE $sample_data_table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			survey_group_id bigint(20) UNSIGNED NOT NULL,
			type enum('questions','responses','survey') DEFAULT NULL,
			data longblob,
			created date DEFAULT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		//TODO: create tables for api/source specific functions and parameters in lieu of hard coding
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $main_sql );
		dbDelta( $source_sql );
		dbDelta( $sample_data_sql );
		dbDelta( $custom_demo_sql );

		add_option('opin_db_version',$opin_db_version);
	}
	/*
		function to insert install data into the source $prefix_opinionnaire_sources table
	*/
	function opin_install_data() {
		global $wpdb;

		$source_table_name = $wpdb->prefix . 'opinionnaire_sources';
		$sources = array(
			0 => array(
				'name' => 'SurveyGizmo',
				'url_base' => 'https://restapi.surveygizmo.com/v4/survey'
			)
		);

		foreach($sources as $source) {
			$dup_check = $wpdb->get_results( "SELECT * FROM $source_table_name WHERE name='{$source['name']}'");
			if(empty($dup_check)) {
				$wpdb->insert(
					$source_table_name,
					array(
						'name' => $source['name'],
						'url_base' => $source['url_base']
					)
				);
			}
		}

		$sample_data_table_name = $wpdb->prefix . 'opinionnaire_sample_data';
		$questions_file = plugin_dir_path( __FILE__ ) . 'sample_data/questions_object.js';
		$responses_file = plugin_dir_path( __FILE__ ) . 'sample_data/responses_object.js';
		$sample_datum = array(
			0 => array(
				'survey_group_id' => 1581595,
				'type' => 'questions',
				'data' => file_get_contents($questions_file),
				'created' => OPIN_SAMPLE_DATA_SNAPSHOT_DATE
			),
			1 => array(
				'survey_group_id' => 1581595,
				'type' => 'responses',
				'data' => file_get_contents($responses_file),
				'created' => OPIN_SAMPLE_DATA_SNAPSHOT_DATE
			)
		);

		foreach($sample_datum as $data) {
			$wpdb->insert(
				$sample_data_table_name,
				array(
					'survey_group_id' => $data['survey_group_id'],
					'type' => $data['type'],
					'data' => $data['data'],
					'created' => $data['created']
				)
			);
		}
	}

