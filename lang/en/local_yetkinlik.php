<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for local_yetkinlik plugin.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action'] = 'Action';
$string['activity_name'] = 'Activity Name';
$string['ai_failed'] = 'AI request failed.';
$string['ai_not_configured'] = 'AI is active but settings are missing.';
$string['ai_prompt_school'] = 'Write a pedagogical analysis and development strategy for the school based on the following competency percentages:';
$string['ai_prompt_student'] = 'Write a short pedagogical analysis for the student based on the following competency percentages:';
$string['ai_system_prompt'] = 'You are an educational assistant. Provide motivational and pedagogical feedback for students or schools.';
$string['allcompetencies'] = 'All Competencies';
$string['alltime'] = 'All time';
$string['allusers'] = 'All Students';
$string['analysisfor'] = 'Competency Analysis: {$a}';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter your OpenAI or Azure OpenAI API key. <a href="https://platform.openai.com/account/api-keys" target="_blank">Click here for OpenAI key</a>.';
$string['arithmetic_mean_activities'] = 'Arithmetic Mean (Activities Overall)';
$string['bluelegend'] = 'Blue: Mostly Achieved (60–79%)';
$string['btn_process_now'] = 'Process Success Rates in Background';
$string['classavg'] = 'Class Average';
$string['classinfo'] = 'Class: {$a}';
$string['classreport'] = 'Class Competency Report';
$string['colorlegend'] = 'Color Legend:';
$string['comment'] = 'Comment';
$string['comment_blue'] = 'Mostly learned topics: {$a}';
$string['comment_green'] = 'Fully learned topics: {$a}';
$string['comment_orange'] = 'Partially learned topics: {$a}';
$string['comment_red'] = 'Not yet achieved topics: {$a}';
$string['compareinfo'] = 'In this report, you can compare your success with the overall course and your class.';
$string['competency'] = 'Competency / Outcome';
$string['competencycode'] = 'Competency Code';
$string['competencyname'] = 'Outcome / Competency';
$string['correct'] = 'Correct';
$string['correctcount'] = 'Correct Count';
$string['courseavg'] = 'Course Average';
$string['creation_date'] = 'Creation Date';
$string['custom'] = 'Evidence';
$string['enable_ai'] = 'Enable AI Integration';
$string['enable_ai_desc'] = 'Enables AI-based pedagogical comments. API key and model selection must be configured below.';
$string['error_no_enrolment'] = 'You cannot view the report because you are not enrolled in this course.';
$string['evidence'] = 'Evidence';
$string['evidence_description'] = 'Success for competency {$a->competency}: {$a->rate}%';
$string['evidence_description_detailed'] = 'Question Success: {$a->quizrate}%, Activity Average: {$a->activityrate}% (Overall Average: {$a->rate}%)';
$string['evidence_lbl_activities'] = 'Activities';
$string['evidence_lbl_average'] = 'Success Criteria Arithmetic Mean';
$string['evidence_lbl_competency'] = 'Competency';
$string['evidence_lbl_quizrate'] = 'Question Success';
$string['evidence_note'] = 'Success for competency {$a->competency}: {$a->rate}%';
$string['filter'] = 'Filter';
$string['filterlabel'] = 'Filter';
$string['general_total_average'] = 'General Total / Average';
$string['generalcomment'] = 'General Comment';
$string['greenlegend'] = 'Green: Fully Achieved (80%+)';
$string['groupcompetency'] = 'Group Competency Analysis';
$string['groupquizcompetency'] = 'Group Quiz Competency Analysis';
$string['label_evaluation_mode'] = 'Select Processing Mode:';
$string['last30days'] = 'Last 30 days';
$string['last90days'] = 'Last 90 days';
$string['managecriteria'] = 'Competency Criteria Definition';
$string['maxrows'] = 'Maximum rows';
$string['maxrows_desc'] = 'Maximum number of rows to display in tables.';
$string['minpercent'] = 'Lower Percentage Limit';
$string['model'] = 'Model';
$string['model_desc'] = 'Enter the model name to use (e.g., gpt-4).';
$string['mode_only_evidence'] = 'Add Evidence Only';
$string['mode_score_by_average'] = 'Add Evidence + Score by Question Success and Other Activities Average';
$string['mode_score_by_question'] = 'Add Evidence + Score by Question Success Percentages';
$string['maxpercent'] = 'Upper Percentage Limit';
$string['myavg'] = 'My Success';
$string['mycompetencies'] = 'My Competency Analyses';
$string['mycompetencyexams'] = 'My Competency-Based Exams';
$string['mycompetencystate'] = 'Competency Status';
$string['mycriteriaprogression'] = 'My Progress Criteria';
$string['myexamanalysis'] = 'My Exam Competency Analysis';
$string['myreportcard'] = 'My Report Card';
$string['nocompetencies'] = 'No competencies found.';
$string['nocompetencyexamdata'] = 'No exam data found for this competency.';
$string['nodatafound'] = 'No completed exam data available to analyze in this course yet.';
$string['nodatastudentcompetency'] = 'No exam data found for this student in this competency.';
$string['noexamdata'] = 'No competency data found for this exam.';
$string['noscaleitemsfound'] = 'No items found for the selected scale.';
$string['no_direct_activities_found'] = 'No non-exam activities directly mapped to this competency (via `competency_modulecomp`) were found.';
$string['orangelegend'] = 'Orange: Partially Achieved (40–59%)';
$string['other_activities_related_title'] = 'Other Activities Related to This Competency';
$string['overallsummarytitle'] = 'General Competency Performance Summary (Exams + Activities Average)';
$string['pdfmystudent'] = '📄 View My Report Card PDF';
$string['pdfreport'] = '📄 PDF Report';
$string['pluginname'] = 'Competency Analysis System';
$string['privacy:metadata'] = 'The competency plugin does not store any personal data.';
$string['privacy:metadata:openai:answertext'] = 'The student\'s response is sent to be evaluated by the AI model.';
$string['privacy:metadata:openai:externalpurpose'] = 'The plugin sends question texts and user responses to the OpenAI API to provide AI-generated feedback and competency analysis.';
$string['privacy:metadata:openai:questiontext'] = 'The text of the question is sent to provide context for the AI analysis.';
$string['process_queued'] = 'Success rate calculation task has been queued and will be completed in the background.';
$string['process_success_desc'] = 'This process calculates students\' success percentages in exam questions and adds them as evidence.';
$string['process_success_heading'] = 'Transfer Percentage Success to Evidence';
$string['process_success_title'] = 'Process Success Rates in Background';
$string['question'] = 'Question';
$string['questioncount'] = 'Question Count';
$string['questionlinks'] = 'Related Question Details';
$string['questionname'] = 'Question Name';
$string['quiz'] = 'Quiz';
$string['recordupdated'] = 'Record successfully updated';
$string['redlegend'] = 'Red: Not Achieved (0–39%)';
$string['report_heading'] = 'Detailed Competency Analysis Report';
$string['report_title'] = 'Detailed Competency Report';
$string['savechanges'] = 'Save Changes';
$string['scaleitem'] = 'Scale Item (Option)';
$string['scalemapping'] = 'Scale Percentage Mapping Management';
$string['schoolpdf'] = 'School PDF Report';
$string['schoolpdfreport'] = 'School General Success Report';
$string['schoolreport'] = 'School General Report';
$string['searchcompetency'] = 'Search competency';
$string['searchquiz'] = 'Search quiz';
$string['searchuserorprept'] = 'Search student or report';
$string['selectcompetency'] = 'Select competency';
$string['selectgroup'] = 'Select group';
$string['selectquiz'] = 'Select quiz';
$string['selectscale'] = 'Select Scale:';
$string['selectstudent'] = 'Select student';
$string['selectuser'] = 'Select student';
$string['show'] = 'Show';
$string['structured_blue'] = '{$a->shortname}: Success rate {$a->rate}%. Mostly learned. Recommendation: Review the missing points.';
$string['structured_green'] = '{$a->shortname}: Success rate {$a->rate}%. Full success achieved. Recommendation: You can move on to advanced activities.';
$string['structured_orange'] = '{$a->shortname}: Success rate {$a->rate}%. Partially learned. Recommendation: Practice by solving more sample questions.';
$string['structured_red'] = '{$a->shortname}: Success rate {$a->rate}%. Sufficient progress not yet achieved. Recommendation: Review the topic and use additional resources.';
$string['student'] = 'Student';
$string['studentanalysis'] = 'Student Competency Analysis';
$string['studentavg'] = 'Student Average';
$string['studentclass'] = 'Competency Status';
$string['studentcompetencyactivity'] = 'My Competency Performance';
$string['studentcompetencydetail'] = 'Student Competency Detail';
$string['studentcompetencyexams'] = 'My Competency-Based Exam Analyses';
$string['studentcriteriaanalysis'] = 'Student Criteria Tracking Panel';
$string['studentexam'] = 'My Exam Competency Analysis';
$string['studentexamanalysis'] = 'Student Exam Analysis';
$string['studentpdfreport'] = 'Competency Development Report';
$string['studentreport'] = 'Competency Report Card';
$string['success'] = 'Success';
$string['successpercent'] = 'Success Percentage';
$string['successrate'] = 'Success Rate (%)';
$string['success_percentage'] = 'Success Percentage';
$string['success_threshold'] = 'Success Threshold';
$string['success_threshold_desc'] = 'Default success percentage for color coding.';
$string['summaryreport'] = 'Competency Success Summary';
$string['teacherstudentcompetency'] = 'Student Competency Analysis';
$string['teacherstudentcompetencyactivity'] = 'Student Competency Activity Analysis';
$string['timeline'] = 'Timeline';
$string['timelineheading'] = 'Competency Progress Over Time';
$string['total'] = 'TOTAL';
$string['user'] = 'Student';
$string['viewattempt'] = 'Review';
$string['visual_report'] = 'Visual report';
$string['yetkinlik:manage'] = 'Manage question-competency mappings';
$string['yetkinlik:viewownreport'] = 'View own competency analysis report';
$string['yetkinlik:viewreports'] = 'View all student competency reports';
