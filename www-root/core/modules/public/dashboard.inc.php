<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This is the main dashboard that people see when they log into Entrada
 * and have not requested another page or module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) exit;

if (!$ENTRADA_ACL->amIAllowed("dashboard", "read")) {

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$DISPLAY_DURATION		= array();
	$poll_where_clause		= "";
	$PREFERENCES			= preferences_load("dashboard");

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/rssreader.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_timestamp.js\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/dashboard-ics.js\"></script>";

	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.weekcalendar.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.qtip.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$JQUERY[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.weekcalendar.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";

	/**
	 * Fetch the latest feeds and links for this user.
	 */
	$dashboard_feeds = dashboard_fetch_feeds();
	$dashboard_links = dashboard_fetch_links();
    
	/**
	 * Display current weather conditions in the sidebar.
	 */
	$sidebar_html = display_weather();
	if ($sidebar_html != "") {
		new_sidebar_item("Weather Forecast", display_weather(), "weather", "open");
    }
    
    //generates courses for use with the ics files
    $COURSE_LIST = array();
    $results = courses_fetch_courses(true, true);
    if ($results) {
        foreach ($results as $result) {
            $COURSE_LIST[$result["course_id"]] = html_encode(($result["course_code"] ? $result["course_code"] . ": " : "") . $result["course_name"]);
        }
	}

    $calendar_http_url = ENTRADA_URL."/calendars".(isset($_SESSION["details"]["private_hash"]) ? "/private-".html_encode($ENTRADA_USER->getActivePrivateHash()) : "")."/".html_encode($ENTRADA_USER->getUsername()).".ics";
    $calendar_webcal_url = str_ireplace(array("https://", "http://"), "webcal://", $calendar_http_url);
    ?>
   
   <?php

	/**
	 * If user is a member of any communities, show them here.
	 */
	$query 		= "	SELECT b.`community_id`, b.`community_url`, b.`community_title`
					FROM `community_members` AS a
					LEFT JOIN `communities` AS b
					ON b.`community_id` = a.`community_id`
					WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
					AND a.`member_active` = '1'
					AND b.`community_active` = '1'
					AND b.`community_template` <> 'course'
					ORDER BY b.`community_title` ASC
					LIMIT 0, 11";
	$results	= $db->GetAll($query);
	if ($results) {
		$sidebar_html  = "<ul class=\"menu\">\n";
		foreach ($results as $key => $result) {
			if ($key < 10) {
				$sidebar_html .= "<li class=\"community\"><a href=\"".ENTRADA_RELATIVE."/community".$result["community_url"]."\">".html_encode($result["community_title"])."</a></li>\n";
			} else {
				$sidebar_html .= "<li><a href=\"".ENTRADA_RELATIVE."/communities\">more ...</a></li>\n";
				break;
			}
		}
		$sidebar_html .= "</ul>\n";
		new_sidebar_item("My Communities", $sidebar_html, "my-communities", "open");
	} else {
		$sidebar_html  = "<div style=\"text-align: center\">\n";
		$sidebar_html .= "	<a href=\"".ENTRADA_RELATIVE."/podcasts\"><img src=\"".ENTRADA_RELATIVE."/images/podcast-dashboard-image.jpg\" width=\"149\" height=\"99\" alt=\"MEdTech Podcasts\" title=\"Subscribe to our Podcast feed.\" border=\"0\"></a><br />\n";
		$sidebar_html .= "	<a href=\"".ENTRADA_RELATIVE."/podcasts\" style=\"color: #557CA3; font-size: 14px\">Podcasts Available</a>";
		$sidebar_html .= "</div>\n";
		new_sidebar_item("Podcasts in iTunes", $sidebar_html, "podcast-bar", "open");
	}

	switch ($ACTION) {
		case "read" :
			if ((isset($_POST["mark_read"])) && (is_array($_POST["mark_read"]))) {
				foreach ($_POST["mark_read"] as $notice_id) {
					if ($notice_id = (int) $notice_id) {
						add_statistic("notices", "read", "notice_id", $notice_id);
					}
				}
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false));
		break;
		default :
			continue;
		break;
	}

	switch ($ENTRADA_USER->getActiveGroup()) {
		case "alumni" :
			$poll_where_clause = "(a.`poll_target` = 'all' OR a.`poll_target` = 'alumni')";;
		break;
		case "faculty" :
			$poll_where_clause = "(a.`poll_target` = 'all' OR a.`poll_target` = 'faculty')";;
		break;
		case "medtech" :
			$poll_where_clause = "(a.`poll_target` = 'all' OR a.`poll_target` = 'staff')";;
		break;
		case "resident" :
			$poll_where_clause = "(a.`poll_target` = 'all' OR a.`poll_target` = 'resident')";;
		break;
		case "staff" :
			$poll_where_clause = "(a.`poll_target` = 'all' OR a.`poll_target` = 'staff')";;
		break;
		case "student" :
		default :
			$cohort = groups_get_cohort($ENTRADA_USER->getID());
			$poll_where_clause = "(a.`poll_target_type` = 'cohort' AND a.`poll_target`='".clean_input($cohort["group_id"], "alphanumeric")."' OR a.`poll_target` = 'all' OR a.`poll_target` = 'students')";
		break;
	}

	if (!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"])) {
		$query = "	SELECT a.`poll_id`
					FROM `poll_questions` AS a
					LEFT JOIN `poll_results` AS b
					ON b.`poll_id` = a.`poll_id`
					AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					WHERE b.`result_id` IS NULL
					AND (`poll_from` = '0' OR `poll_from` <= '".time()."')
					AND (`poll_until` = '0' OR `poll_until` >= '".time()."')
					".(($poll_where_clause) ? "AND ".$poll_where_clause : "")."
					ORDER BY RAND() LIMIT 1";
		$result	= $db->GetRow($query);
		if ($result) {
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"] = $result["poll_id"];
		} else {
			$query = "	SELECT a.`poll_id`
						FROM `poll_questions` AS a
						LEFT JOIN `poll_results` AS b
						ON b.`poll_id` = a.`poll_id`
						WHERE b.`result_id` IS NOT NULL
						AND (`poll_from` = '0' OR `poll_from` <= '".time()."')
						AND (`poll_until` = '0' OR `poll_until` >= '".time()."')
						ORDER BY RAND() LIMIT 1";
			$result	= $db->GetRow($query);
			if ($result) {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"] = $result["poll_id"];
			} else {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"] = 0;
			}
		}
	}

	if ($_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"]) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/poll-js.php\"></script>\n";

		new_sidebar_item($translate->_("Quick Polls"), poll_display($_SESSION[APPLICATION_IDENTIFIER]["tmp"][$MODULE]["poll_id"]), "quick-poll", "open");
	}

	if (defined("ENABLE_NOTICES") && ENABLE_NOTICES) {
		$notices_to_display = Models_Notice::fetchUserNotices();
		if ($notices_to_display && ($total_notices = count($notices_to_display))) { ?>
			<form action="<?php echo ENTRADA_RELATIVE; ?>/dashboard?action=read" method="post">
				<div class="dashboard-notices alert">
					<div class="row-fluid">
						<div class="span8">
							<h2><?php echo APPLICATION_NAME ." ". $translate->_("Message Center");?></h2>
						</div>
						<div class="span4">
							<a class="btn btn-small pull-right previous-notices" href="<?php echo ENTRADA_URL; ?>/messages"><i class="icon-eye-open"></i> Previously Read Messages</a>
						</div>
					</div>
					<?php
					foreach ($notices_to_display as $announcement) {
						echo "<div id=\"notice_box_".(int) $announcement["notice_id"]."\" class=\"new-notice\">";
						echo "  <label class=\"checkbox\"><input type=\"checkbox\" name=\"mark_read[]\" id=\"notice_msg_".(int) $announcement["notice_id"]."\" value=\"".(int) $announcement["notice_id"]."\" /> ";
						echo "	<strong>".date(DEFAULT_DATE_FORMAT, $announcement["updated_date"])."</strong>";
						echo    ($announcement["lastname"] ? " <small>by ".html_encode($announcement["firstname"]." ".$announcement["lastname"])."</small>" : "");
						echo "  </label>\n";
						echo "	<div class=\"space-left\">".trim(clean_input($announcement["notice_summary"], "html"))."</div>";
						echo "</div>";
					}
					?>
				</div>
				<a href="<?php echo ENTRADA_URL; ?>/rss/<?php echo $ENTRADA_USER->getUsername() ?>.rss" target="_blank" class="btn-mini"><i class="icon-fire"></i> Subscribe to RSS Feed</a>
				<button class="btn btn-small btn-success pull-right"><i class="icon-ok icon-white"></i> Mark As Read</button>
				<div class="clearfix"></div>
			</form>

		<?php
		} else { ?>
			<div class="well no-dashboard-notices">
				<div class="row-fluid">
					<div class="span8">
						<h2><?php echo APPLICATION_NAME ." ". $translate->_("Message Center");?></h2>
					</div>
					<div class="span4 pull-right">
						<a class="btn btn-small pull-right previous-notices" href="<?php echo ENTRADA_URL; ?>/messages"><i class="icon-eye-open"></i> Previously Read Messages</a>
					</div>
				</div>
				<p>The <?php echo  $translate->_("Message Center"); ?> is currently empty.</p>
			</div>
		<?php
		}
	}
	
	switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]) {
		case "medtech" :
		case "student" :
			$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/dashboard", "title" => "Student Dashboard");

			/**
			 * How did this person not get assigned this already? Mak'em new.
			 */
			if (!isset($cohort) || !$cohort) {
				$query = "SELECT *
						FROM `groups`
						WHERE `group_id` = ".$db->qstr(fetch_first_cohort());
				$cohort = $db->GetRow($query);
			}

			$HEAD[]	= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Notices\" href=\"".ENTRADA_URL."/notices/".$cohort["group_id"]."\" />";

			if (!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = time();
			}


			$display_schedule_tabs	= false;

			if ($ENTRADA_ACL->amIAllowed("clerkship", "read")) {
				$query = "SELECT a.*, c.`region_name`, d.`aschedule_id`, d.`apartment_id`, e.`rotation_title`
							FROM `".CLERKSHIP_DATABASE."`.`events` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
							ON b.`event_id` = a.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
							ON c.`region_id` = a.`region_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS d
							ON d.`event_id` = a.`event_id`
							AND d.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
							AND d.`aschedule_status` = 'published'
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS e
							ON e.`rotation_id` = a.`rotation_id`
							WHERE a.`event_finish` >= ".$db->qstr(strtotime("00:00:00"))."
							AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
							AND b.`econtact_type` = 'student'
							AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
							ORDER BY a.`event_start` ASC";
				$clerkship_schedule	= $db->GetAll($query);
				if ($clerkship_schedule) {
					$display_schedule_tabs = true;
				}
			}

			if ($display_schedule_tabs) {
				?>
				<div class="tab-pane" id="clerkship-schedule">
					<div class="tab-page" style="height: auto;">
						<h3 class="tab">Clerkship Schedule</h3>
						<?php
						if ($clerkship_schedule) {
							?>
							<div class="display-notice">
								<strong>Notice:</strong> Keeping the Undergrad office informed of clerkship schedule changes is very important. This information is used to ensure you can graduate; therefore, if you see any inconsistencies, please let us know immediately: <a href="javascript:sendClerkship('<?php echo ENTRADA_RELATIVE; ?>/agent-clerkship.php')">click here</a>.
							</div>
							<h2>Remaining Clerkship Rotations</h2>
							<div class="pull-right space-below">
								<a href="<?php echo ENTRADA_RELATIVE."/clerkship/electives?section=add";?>" class="btn btn-success btn-small"><i class="icon-plus-sign icon-white"></i>Add Elective</a>
								<a href="<?php echo ENTRADA_RELATIVE."/clerkship/logbook?section=add&event=".$clerkship_schedule[0]["event_id"];?>" class="btn btn-success btn-small"><i class="icon-plus-sign icon-white"></i>Log Encounter</a>
							</div>
							<?php
							$query = "	SELECT `rotation_id`
										FROM `".CLERKSHIP_DATABASE."`.`events`
										WHERE `event_id` = ".$db->qstr($clerkship_schedule[0]["event_id"]);
							$ROTATION_ID = $db->GetOne($query);
							?>
							<div style="clear: both"></div>
							<table class="tableList" cellspacing="0" summary="List of Remaining Clerkship Rotations">
								<colgroup>
									<col class="modified" />
									<col class="type" />
									<col class="title" />
									<col class="region" />
									<col class="date-smallest" />
									<col class="date-smallest" />
								</colgroup>
								<thead>
									<tr>
										<td class="modified">&nbsp;</td>
										<td class="type">Event Type</td>
										<td class="title">Rotation Name</td>
										<td class="region">Region</td>
										<td class="date-smallest">Start Date</td>
										<td class="date-smallest">Finish Date</td>
									</tr>
								</thead>
								<tbody>
								<?php
								foreach ($clerkship_schedule as $result) {
									if ((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
										$bgcolour = "#E7ECF4";
										$is_here = true;
									} else {
										$bgcolour = "#FFFFFF";
										$is_here = false;
									}

									if ((int) $result["aschedule_id"]) {
										$apartment_available = true;
										$click_url = ENTRADA_RELATIVE."/regionaled/view?id=".$result["aschedule_id"];
									} else {
										$apartment_available = false;
										$click_url = "";
									}

									if (!isset($result["region_name"]) || $result["region_name"] == "") {
										$result_region = clerkship_get_elective_location($result["event_id"]);
										$result["region_name"] = $result_region["region_name"];
										$result["city"] = $result_region["city"];
									} else {
										$result["city"] = "";
									}

									$event_title = clean_input($result["event_title"], array("htmlbrackets", "trim"));

									$cssclass = "";
									$skip = false;

									if ($result["event_type"] == "elective") {
										switch ($result["event_status"]) {
											case "approval":
												$elective_word = "Pending";
												$cssclass = " class=\"in_draft\"";
												$click_url = ENTRADA_RELATIVE."/clerkship/electives?section=edit&id=".$result["event_id"];
												$skip = false;
											break;
											case "published":
												$elective_word = "Approved";
												$cssclass = " class=\"published\"";
												$click_url = ENTRADA_RELATIVE."/clerkship/electives?section=view&id=".$result["event_id"];
												$skip = false;
											break;
											case "trash":
												$elective_word = "Rejected";
												$cssclass = " class=\"rejected\"";
												$click_url = ENTRADA_RELATIVE."/clerkship/electives?section=edit&id=".$result["event_id"];
												$skip = true;
											break;
											default:
												$elective_word = "";
												$cssclass = "";
											break;
										}

										$elective = true;
									} else {
										$elective = false;
										$skip = false;
									}

									if (!$skip) {
										echo "<tr".(($is_here) && $cssclass != " class=\"in_draft\"" ? " class=\"current\"" : $cssclass).">\n";
										echo "	<td class=\"modified\">".(($apartment_available) ? "<a href=\"".$click_url."\">" : "")."<img src=\"".ENTRADA_RELATIVE."/images/".(($apartment_available) ? "housing-icon-small.gif" : "pixel.gif")."\" width=\"16\" height=\"16\" alt=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" title=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" style=\"border: 0px\" />".(($apartment_available) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"type\">".(($apartment_available || $elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").(($elective) ? "Elective".(($elective_word != "") ? " (".$elective_word.")" : "") : "Core Rotation").(($apartment_available || $elective) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"title\"><span title=\"".$event_title."\">".(($apartment_available) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").limit_chars(html_decode($event_title), 55).(($apartment_available) ? "</a>" : "")."</span></td>\n";
										echo "	<td class=\"region\">".(($apartment_available || $elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").html_encode((($result["city"] == "") ? limit_chars(($result["region_name"]), 30) : $result["city"])).(($apartment_available || $elective) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"date-smallest\">".(($apartment_available) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").date("D M d/y", $result["event_start"]).(($apartment_available) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"date-smallest\">".(($apartment_available) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").date("D M d/y", $result["event_finish"]).(($apartment_available) ? "</a>" : "")."</td>\n";
										echo "</tr>\n";
									}
								}
								?>
								</tbody>
							</table>
							<div style="margin-top: 15px; text-align: right">
								<a href="<?php echo ENTRADA_RELATIVE; ?>/clerkship" style="font-size: 11px">Click here to view your full schedule.</a>
							</div>
							<?php
						}
						?>
					</div>
					<div class="tab-page" style="height: auto">
						<h3 class="tab">Learning Event Schedule</h3>
						<?php
			}

            // If saturday or sunday add 48 or 24 hours to the current time so the calendar displays the correct week, otherwise use the current time.
            switch (date("N", time())) {
                case '6' :
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["caltime"] = time() + (86400 * 2);
                break;
                case '7' :
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["caltime"] = time() + 86400;
                break;
                default :
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["caltime"] = time();
                break;
            }
            ?>
            <script type="text/javascript">
            var year = new Date().getFullYear();
            var month = new Date().getMonth();
            var day = new Date().getDate();

            jQuery(document).ready(function() {
                jQuery('#dashboardCalendar').weekCalendar({
                    date : new Date(<?php echo ((($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["caltime"]) ? $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["caltime"] : time()) * 1000); ?>),
                    dateFormat : 'M d, Y',
                    height: function($calendar) {
                        return 600;
                    },
                    daysToShow: 5,
                    firstDayOfWeek: 1,
                    useShortDayNames: true,
                    allowCalEventOverlap: true,
                    overlapEventsSeparate: false,
                    timeslotsPerHour: 4,
                    timeslotHeight: 19,
                    buttons: false,
                    readonly: true,
                    businessHours : { start: 8, end: 18, limitDisplay : false },
                    eventRender : function(calEvent, $event) {
                        switch (calEvent.type) {
                            case 3 :
                                $event.find('.wc-time').css({'backgroundColor': '#5F718F', 'border':'1px solid #354868'});
                                $event.css({'backgroundColor':'#7E92B5'});
                            break;
                            case 2 :
                                $event.find('.wc-time').css({'backgroundColor':'#9E9E48', 'border':'1px solid #8A8A2D'});
                                $event.css({'backgroundColor':'#B5B37E'});

                                if (calEvent.updated) {
                                    calEvent.title += '<div class="wc-updated-event calEventUpdated' + calEvent.id + '"> Last updated ' + calEvent.updated + '</div>';
                                }
                            break;
                            default :
                            break;
                        }

                        $event.find('.wc-time,.wc-title').qtip({
                            content: {
                                text: '<img class="throbber" src="<?php echo ENTRADA_RELATIVE; ?>/images/throbber.gif" alt="Loading..." />',
                                url: '<?php echo ENTRADA_RELATIVE; ?>/api/events.api.php?id=' + calEvent.id + (calEvent.drid != 'undefined' ? '&drid=' + calEvent.drid : ''),
                                title: {
                                    text: '<a href="<?php echo ENTRADA_RELATIVE; ?>/events?' + (calEvent.drid != 'undefined' ? 'drid=' + calEvent.drid : 'id=' + calEvent.id) + '">' + calEvent.title + '</a>',
                                    button: 'Close'
                                }
                            },
                            position: {
                                corner: {
                                    target: 'topMiddle',
                                    tooltip: 'topMiddle'
                                },
                                adjust: {
                                    screen: true
                                }
                            },
                            show: {
                                when: 'click',
                                solo: true
                            },
                            hide: 'unfocus',
                            style: {
                                tip: true,
                                border: { width: 0, radius: 4 },
                                name: 'light',
                                width: 485
                            }
                        });
                    },
                    eventClick : function(calEvent, $event) {
                        if (calEvent.type == 2) {
                            $event.find('.wc-time').animate({'backgroundColor':'#2B72D0', 'border':'1px solid #1B62C0'}, 500);
                            $event.animate({'backgroundColor':'#68A1E5'}, 500);
                            $event.find('.calEventUpdated' + calEvent.id).fadeOut(500);
                        }
                    },
                    externalDates : function (calendar) {
                        jQuery('#currentDateInfo').html(calendar.find('.wc-day-1').html() + ' - ' + calendar.find('.wc-day-5').html());
                    },
                    data : '<?php echo ENTRADA_RELATIVE; ?>/calendars/<?php echo html_encode($_SESSION["details"]["username"]); ?>.json'
                });
                
            });
            
            jQuery(document).ready(function() {
                //set inital settings
                jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download").removeClass('btn-group');
                //hides Subscibe versus Download till a course is chosen so they can't download the all link.


                jQuery('#calendar-ics-btn').click(function() {
                    show_hide_calendar_ics();
                });
                jQuery('div#dashboard_ics_calendar .content-calendar #close').click(function() {
                    show_hide_calendar_ics();
                });

                jQuery("div#dashboard_ics_calendar .content-calendar #all-course .btn").click(function() {
                    jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe .span10").hide();
                    jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download .span10").hide(); 
                    //hides the course download/subscribe buttons if no course is set
                    if (!jQuery("div#dashboard_ics_calendar .content-calendar #course-quick-select").val() == "") {
                        update_html_ics('<?php echo $calendar_http_url;?>', '<?php echo $calendar_webcal_url;?>', jQuery(this).data('type'), true);
                    } else {
                        update_html_ics('<?php echo $calendar_http_url;?>', '<?php echo $calendar_webcal_url;?>', jQuery(this).data('type'), false);
                    }
                    course_switcher(this, jQuery(this).data('type'));
                 });
                jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download .btn").click(function() {
                    download_switcher(this, jQuery(this).data('type'));
                 });

                jQuery("div#dashboard_ics_calendar .content-calendar #course-quick-select").change(function() {
                   update_html_ics('<?php echo $calendar_http_url;?>', '<?php echo $calendar_webcal_url;?>', 'course', true);
                })                     
            });
		
            function setDateValue(field, date) {
                timestamp = (getMSFromDate(date) * 1000);

                if (field.value != timestamp) {
                    field.value = getMSFromDate(date);
                    jQuery('#dashboardCalendar').weekCalendar('gotoWeek', new Date(timestamp));
                }

                return;
            }
				
			function showCalendarLink(link) {
				jQuery("#calendar-link-wrapper").html("<input id=\"calendar-link-input\" style=\"margin-bottom:0;\" type=\"text\" value=\"" + link + "\" />");
				jQuery("#calendar-link-input").select();
			}
            </script>
            <div class="row-fluid">
                <div class="span6">
                    <div class="btn-group">
                        <button class="btn wc-prev" onclick="jQuery('#dashboardCalendar').weekCalendar('prevWeek');"><i class="icon-chevron-left"></i></button>
                        <button class="btn content-small" id="calendar-holder" onclick="showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1)"><span id="currentDateInfo"></span></button>
                        <button class="btn wc-next" onclick="jQuery('#dashboardCalendar').weekCalendar('nextWeek');"><i class="icon-chevron-right"></i></button>
                    </div>
                </div>
                <div class="span6">
                    <button class="btn wc-today" onclick="jQuery('#dashboardCalendar').weekCalendar('today');"><i class="icon-refresh"></i></button>
                    <h1 class="pull-right"><strong>My</strong> Schedule</h1>
                </div>
            </div>

            <div id="dashboardCalendar"></div>

            <div id="dashboard_ics_calendar_container">
                <div id="dashboard_ics_calendar" class="hidden">
                    <div class="panel-head">Subscribe to Calendar or Download Calendar</div>
                    <div class="content-calendar">
                        <div class="row-fluid">
                            <div class="btn-group" id="all-course">
                                <button class="btn active" data-type="all">All Calendars</button>
                                <button class="btn" data-type="course">Individual Course Calendar</button>
                            </div>
                        </div>
                        <div class="row-fluid" id="course-selector">
                            <div class="span2"><strong>Course Select: </strong></div>
                            <div class="span10">
                                <select id="course-quick-select" name="course-quick-select">
                                <option value="">-- Select a Course --</option>
                                <?php
                                foreach ($COURSE_LIST as $course_id => $course_name) {
                                    echo "<option value=\"".$course_id."\">".$course_name."</option>\n";
                                }
                                ?>
                                </select>
                            </div>
                        </div>

                        <div class="row-fluid">
                            <div class="btn-group" id="subscribe-download">
                                <button class="btn active" data-type="subscribe">Subscribe</button>
                                <button class="btn" data-type="download">Download</button>
                            </div>
                        </div>
                        <div class="row-fluid">
                            <p>The subscribed version of the ics file will be update automatically with changes made to the learning events, but is not editable. The download ics file can be imported and edited, but will not be updated automatically although you can re-download and upload again. </p>
                        </div>

                        <div id="calendar-subscribe" class="visable">
                            <div class="row-fluid">
                                <div class="span2"><strong>Subscribe</strong></div>
                                <div class="span10">
                                    <span id="calendar-link-wrapper"></span>
                                    <a class="btn btn-small" href="javascript:showCalendarLink('<?php echo $calendar_http_url; ?>')" id="copy-link"><i class="icon-link"></i> Copy Subscription Link</a>
                                    <a class="btn btn-info btn-small" href="<?php echo $calendar_webcal_url; ?>" id="subscribe-calendar-btn"><i class="icon-calendar icon-white"></i> Subscribe to Calendar</a>
                                </div>
                            </div>
                        </div>
                        <div id="calendar-download" class="hidden">
                            <div class="row-fluid">
                                <div class="span2"><strong>Download</strong></div>
                                <div class="span10"><a class="btn btn-info btn-small" href="<?php echo $calendar_http_url;?>"><i class="icon-calendar icon-white"></i> Download Calendar</a></div>
                            </div>
                        </div>
                        <div class="btn pull-right hidden" id="close">Close</div>
                        <div class="cornerarrow"></div>
                    </div>
                </div>
            </div>
            
            <div class="pull-right">
                <a class="btn btn-info btn-small" id="calendar-ics-btn"><i class="icon-calendar icon-white"></i> Subscribe to Calendar or Download Calendar</a>
            </div>
            <?php
			if ($display_schedule_tabs) {
					?>
					</div>
				</div>
				<script type="text/javascript">setupAllTabs(true);</script>
				<?php
			}
			echo "<form action=\"\" method=\"get\">\n";
			echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
			echo "</form>\n";
		break;
		case "resident" :
		case "faculty" :
			$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE, "title" => ucwords($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"])." Dashboard");

			/**
			 * Update requested timestamp to display.
			 * Valid: Unix timestamp
			 */
			if ((isset($_GET["dlength"])) && ($dlength = (int)	trim($_GET["dlength"])) && ($dlength >= 1) && ($dlength <= 4)) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] = $dlength;

				$_SERVER["QUERY_STRING"] = replace_query(array("dlength" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"])) {
					$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] = 2; // Defaults to this term.
				}
			}

			switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"]) {
				case 1 :	// Last Term
					if (date("n", time()) <= 6) {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 7, 1, (date("Y", time()) - 1));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 12, 31, (date("Y", time()) - 1));
					} else {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
					}
					break;
				case 3 :	// This Month
					$DISPLAY_DURATION["start"]		= mktime(0, 0, 0, date("n", time()), 1, date("Y", time()));
					$DISPLAY_DURATION["end"]		= mktime(0, 0, 0, date("n", time()), date("t", time()), date("Y", time()));
					break;
				case 4 :	// Next Term
					if (date("n", time()) <= 6) {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
					} else {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 1, 1, (date("Y", time()) + 1));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 6, 30, (date("Y", time()) + 1));
					}
					break;
				case 2 :	// This Term
				default :
					if (date("n", time()) <= 6) {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
					} else {
						$DISPLAY_DURATION["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
						$DISPLAY_DURATION["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
					}
					break;
			}

            $results = events_fetch_filtered_events(
                        $ENTRADA_USER->getActiveId(),
                        $ENTRADA_USER->getActiveGroup(),
                        $ENTRADA_USER->getActiveRole(),
                        $ENTRADA_USER->getActiveOrganisation(),
                        "date",
                        "asc",
                        "custom",
                        $DISPLAY_DURATION["start"],
                        $DISPLAY_DURATION["end"],
                        events_filters_defaults($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveGroup(), $ENTRADA_USER->getActiveRole(), $ENTRADA_USER->getActiveOrganisation()),
                        false,
                        0,
                        0,
                        0,
                        false);
            $TOTAL_ROWS = count($results["result_ids_map"]);
			?>
			<table style="width: 100%" cellspacing="0" cellpadding="0" border="0" summary="Weekly Student Calendar">
				<tr>
					<td style="padding-bottom: 3px; text-align: left; vertical-align: middle; white-space: nowrap">
						<h1>My Teaching Events</h1>
					</td>
					<td style="padding-bottom: 3px; text-align: right; vertical-align: middle; white-space: nowrap">
						<form id="dlength_form" action="<?php echo ENTRADA_RELATIVE; ?>" method="get">
							<label for="dlength" class="content-small">Events taking place:</label>
							<select id="dlength" name="dlength" onchange="document.getElementById('dlength_form').submit()">
								<option value="1"<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] == 1) ? " selected=\"selected\"" : ""); ?>>Last Term</option>
								<option value="2"<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] == 2) ? " selected=\"selected\"" : ""); ?>>This Term</option>
								<option value="3"<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] == 3) ? " selected=\"selected\"" : ""); ?>>This Month</option>
								<option value="4"<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"] == 4) ? " selected=\"selected\"" : ""); ?>>Next Term</option>
							</select>
						</form>
					</td>
				</tr>
			</table>
			<?php
			if ($results) {
				?>
				<div id="list-of-learning-events" style="max-height: 300px; overflow: auto">
					<div style="background-color: #FAFAFA; padding: 3px; border: 1px #9D9D9D solid; border-bottom: none">
						<img src="<?php echo ENTRADA_RELATIVE; ?>/images/lecture-info.gif" width="15" height="15" alt="" title="" style="vertical-align: middle" />
										<?php echo "Found ".$TOTAL_ROWS." event".(($TOTAL_ROWS != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $DISPLAY_DURATION["start"])."</strong> to <strong>".date("D, M jS, Y", $DISPLAY_DURATION["end"])."</strong>.\n"; ?>
					</div>
					<table class="tableList" cellspacing="0" summary="List of Learning Events">
						<colgroup>
							<col class="modified" />
							<col class="date" />
							<col class="course-code" />
							<col class="title" />
							<col class="attachment" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified" id="colModified">&nbsp;</td>
								<td class="date sortedASC"><div class="noLink">Date &amp; Time</div></td>
								<td class="course-code">Course</td>
								<td class="title">Event Title</td>
								<td class="attachment">&nbsp;</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($results["events"] as $result) {
                                $attachments = attachment_check($result["event_id"]);
                                $url = ENTRADA_RELATIVE."/admin/events?section=content&id=".$result["event_id"];
                				$accessible = true;

                                if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
                                    $accessible = false;
                                }

                                if (((int) $result["last_visited"]) && ((int) $result["last_visited"] < (int) $result["updated_date"])) {
                                    $is_modified = true;
                                    $modified++;
                                } else {
                                    $is_modified = false;
                                }

                                echo "<tr id=\"event-".$result["event_id"]."\" class=\"event".(!$accessible ? " na" : "")."\">\n";
                                echo "	<td class=\"modified\">".(($is_modified) ? "<img src=\"".ENTRADA_RELATIVE."/images/lecture-modified.gif\" width=\"15\" height=\"15\" alt=\"This event has been modified since your last visit on ".date(DEFAULT_DATE_FORMAT, $result["last_visited"]).".\" title=\"This event has been modified since your last visit on ".date(DEFAULT_DATE_FORMAT, $result["last_visited"]).".\" style=\"vertical-align: middle\" />" : "<img src=\"".ENTRADA_RELATIVE."/images/pixel.gif\" width=\"15\" height=\"15\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />")."</td>\n";
                                echo "	<td class=\"date\"><a href=\"".$url."\">".date(DEFAULT_DATE_FORMAT, $result["event_start"])."</a></td>\n";
                                echo "	<td class=\"course-code\"><a href=\"".$url."\">".html_encode($result["course_code"])."</a></td>\n";
                                echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">".html_encode($result["event_title"])."</a></td>\n";
                                echo "	<td class=\"attachment\">".(($attachments) ? "<img src=\"".ENTRADA_RELATIVE."/images/attachment.gif\" width=\"16\" height=\"16\" alt=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" title=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" />" : "<img src=\"".ENTRADA_RELATIVE."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />")."</td>\n";
                                echo "</tr>\n";
							}
							?>
						</tbody>
					</table>
				</div>
				<?php
			} else {
				?>
				<div style="padding: 10px; background-color: #FAFAFA; border: 1px #9D9D9D solid">
					There is no record of any teaching events in the system for
					<?php
					switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dlength"]) {
						case 1 :
							echo "<strong>last term</strong>.";
						break;
						case 3 :
							echo "<strong>this month</strong>.";
						break;
						case 4 :
							echo "<strong>next term</strong>.";
						break;
						case 2 :
						default :
							echo "<strong>this term</strong>.";
						break;
					}
					?>
					<br /><br />
					You can switch the display period by selecting a different date period in the &quot;Events taking place&quot; box above.
				</div>
				<?php
			}
			?>
			<div style="text-align: right; margin-top: 5px">
				<a href="<?php echo ENTRADA_URL; ?>/calendars<?php echo ((isset($_SESSION["details"]["private_hash"])) ? "/private-".html_encode($_SESSION["details"]["private_hash"]) : ""); ?>/<?php echo html_encode($_SESSION["details"]["username"]); ?>.ics" class="feeds ics">Subscribe to Calendar</a>
			</div>
			<?php
		break;
		case "staff" :
		default :
			continue;
		break;
	}

	/**
	 * Add the dashboard links to the Helpful Links sidebar item.
	 */
	if ((is_array($dashboard_links)) && (count($dashboard_links))) {
		$sidebar_html  = "<ul class=\"menu\">";
		foreach ($dashboard_links as $link) {
			if ((trim($link["title"])) && (trim($link["url"]))) {
				$sidebar_html .= "<li class=\"link\"><a href=\"".html_encode($link["url"])."\" title=\"".(isset($link["description"]) && ($link["description"]) ? html_encode($link["description"]) : html_encode($link["title"]))."\"".(($link["target"]) ? " target=\"".html_encode($link["target"])."\"" : "").">".html_encode($link["title"])."</a></li>\n";
			}
		}
		$sidebar_html .= "</ul>";

		new_sidebar_item("Helpful Links", $sidebar_html, "helpful-links", "open");
	}

	/**
	 * Check if preferences need to be updated on the server at this point.
	 */
	preferences_update($MODULE, $PREFERENCES);
	?>
	<div class="rss-add">
		<a id="add-rss-feeds-link" href="#edit-rss-feeds" class="feeds add-rss">Add RSS Feed</a>
		<a id="edit-rss-feeds-link" href="#edit-rss-feeds" class="feeds edit-rss">Modify RSS Feeds</a>

		<div id="rss-edit-details" class="display-generic" style="display: none;">
			While you are in <strong>edit mode</strong> you can rearrange the feeds below by dragging them to your preferred location. You can also <a href="#edit-rss-feeds" id="rss-feed-reset">reset this page to the default RSS feeds</a> if you would like. <span id="rss-save-results">&nbsp;</span>
		</div>
		<div id="rss-add-details">
			<form id="rss-add-form" class="form-horizontal">
                <h2>Add RSS Feed</h2>
                <p>If you would like to add an additional news feed to your <?php echo APPLICATION_NAME; ?> Dashboard, simple provide us with the feed URL below.</p>

                <div id="rss-add-status"></div>

                <div class="control-group">
                    <label class="control-label form-required" for="rss-add-title">RSS Feed Title:</label>
                    <div class="controls">
                        <input id="rss-add-title" class="input-xlarge" placeholder="Your personalized feed title" />
					</div>
                </div>

                <div class="control-group">
                    <label class="control-label form-required" id="rss-add-url-label" for="rss-add-url">RSS Feed URL:</label>
                    <div class="controls">
                        <input id="rss-add-url" class="input-xlarge" value="http://" />
					</div>
                </div>

                <input type="button" class="btn" id="add-rss-feeds-close-link" value="Cancel" />
				<input type="submit" class="pull-right btn btn-primary" id="rss-add-button" value="Add" />
			</form>
		</div>
	</div>
	<script type="text/javascript">
		var CROSS_DOMAIN_PROXY_URL = "<?php echo ENTRADA_RELATIVE."/serve-remote-feed.php"; ?>";
		var SPINNER_URL = "<?php echo ENTRADA_RELATIVE."/images/loading.gif" ?>";
		var DASHBOARD_API_URL = "<?php echo ENTRADA_RELATIVE."/api/dashboard.api.php"; ?>";
		var SUCCESS_IMAGE_URL = "<?php echo ENTRADA_RELATIVE."/images/question-correct.gif"; ?>";
		var ERROR_IMAGE_URL = "<?php echo ENTRADA_RELATIVE."/images/question-correct.gif"; ?>";
	</script>
	<div id="dashboard-syndicated-content">
		<ul id="rss-list-1" class="rss-list first">
			<?php
			if ((is_array($dashboard_feeds)) && (count($dashboard_feeds))) {
				$list_2 = false;
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feed_break"]) || $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feed_break"] < 0) {
					$break = count($dashboard_feeds)/2;
				} else {
					$break = $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feed_break"];
				}

				for ($i = 0; $i < count($dashboard_feeds); $i++) {

					if ($i >= $break && !$list_2) {
						$list_2 = true;
						echo "</ul>
						<ul id=\"rss-list-2\" class=\"rss-list\">\n";
					}

					$feed = $dashboard_feeds[$i];
					echo "<li> \n";
					echo "  <h2 class=\"rss-title\">".$feed["title"]."</h2>\n";
					echo "  <div class=\"rss-content\" data-feedurl=\"".$feed["url"]."\"></div>\n";

					if (isset($feed["removable"]) && $feed["removable"] == true) {
						echo "<a href=\"#\" class=\"rss-remove-link\">Remove This Feed</a>\n";
					}

					echo "</li>\n";
				}
				if (!$list_2) {
					$list_2 = true;
					echo "</ul>\n<ul id=\"rss-list-2\" class=\"rss-list\">\n";
				}
			}
			?>
		</ul>
		<div class="clear"></div>
	</div>
	<?php
}