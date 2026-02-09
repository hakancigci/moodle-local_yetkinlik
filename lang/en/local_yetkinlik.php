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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for local_yetkinlik plugin.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General Strings.
$string['pluginname'] = 'Competency Plugin';
$string['privacy:metadata'] = 'The Yetkinlik plugin does not store any personal data.';
$string['show'] = 'Show';
$string['savechanges'] = 'Save changes';
$string['recordupdated'] = 'Record updated successfully';

// Navigation & Roles.
$string['user'] = 'Student';
$string['student'] = 'Student';
$string['allusers'] = 'All students';
$string['competency'] = 'Competency';
$string['allcompetencies'] = 'All competencies';
$string['competencycode'] = 'Competency Code';

// Reports General.
$string['studentanalysis'] = 'Student Analysis';
$string['classreport'] = 'Class Report';
$string['pdfreport'] = '📄 PDF Report';
$string['courseavg'] = 'Course Avg.';
$string['classavg'] = 'Class Avg.';
$string['studentavg'] = 'Student Avg.';
$string['evidence'] = 'Evidence';
$string['success'] = 'Success';
$string['total'] = 'TOTAL';
$string['quiz'] = 'Quiz';
$string['question'] = 'Question';
$string['correct'] = 'Correct';

// Teacher/Student Competency Analysis.
$string['teacherstudentcompetency'] = 'Student Competency Analysis';
$string['selectstudent'] = 'Select student';
$string['selectcompetency'] = 'Select competency';
$string['nodatastudentcompetency'] = 'No quiz data found for this student in this competency.';
$string['studentcompetencydetail'] = 'Student Competency Detail';

// My Report Card & Student View.
$string['studentclass'] = 'Competency Analysis';
$string['studentreport'] = 'My Competency Report';
$string['myreportcard'] = 'My Report Card';
$string['myexamanalysis'] = 'My Exam Analysis';
$string['mycompetencyexams'] = 'My Competency Exams';
$string['mycompetencystate'] = 'My Competency State';
$string['mycompetencies'] = 'My Competency Reports';
$string['questioncount'] = 'Number of Questions';
$string['correctcount'] = 'Number of Correct';
$string['successrate'] = 'Success Rate (%)';
$string['pdfmystudent'] = '📄 View My PDF Report';
$string['studentpdfreport'] = 'Competency Report';
$string['studentanalysis'] = 'My Competency Comparison Report'; 
$string['analysisfor'] = 'Competency Analysis: {$a}'; 
$string['compareinfo'] = 'In this report, you can compare your own performance with the overall course average and your class average.';
$string['classinfo'] = 'Class: {$a}'; 
$string['competencyname'] = 'Competency / Skill';
$string['courseavg'] = 'Course Average'; 
$string['classavg'] = 'Class Average';
$string['myavg'] = 'My Achievement'; 
$string['nodatafound'] = 'No completed quiz data found for analysis in this course yet.'; 
$string['error_no_enrolment'] = 'You are not enrolled in this course, therefore you cannot view this report.';

// Legends.
$string['colorlegend'] = 'Color Legend:';
$string['redlegend'] = 'Red: Not achieved (0–39%)';
$string['orangelegend'] = 'Orange: Partially achieved (40–59%)';
$string['bluelegend'] = 'Blue: Mostly achieved (60–79%)';
$string['greenlegend'] = 'Green: Fully achieved (80%+)';

// Exam Analysis.
$string['studentexam'] = 'My Exam Competency Analysis';
$string['selectquiz'] = 'Select exam';
$string['successpercent'] = 'Success %';
$string['noexamdata'] = 'No competency data found for this exam.';

// Competency Based Exams.
$string['studentcompetencyexams'] = 'My Competency-Based Exam Analysis';
$string['nocompetencyexamdata'] = 'No exam data found for this competency.';

// Group & School.
$string['groupcompetency'] = 'Group Competency Analysis';
$string['selectgroup'] = 'Select group';
$string['groupquizcompetency'] = 'Group Quiz Competency Analysis';
$string['schoolpdfreport'] = 'School General Achievement Report';
$string['schoolreport'] = 'School General Report';
$string['schoolpdf'] = 'School PDF Report';

// AI Integration.
$string['enable_ai'] = 'Enable AI integration';
$string['enable_ai_desc'] = 'Enable AI-based pedagogical comments. API key and model selection are required below.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter your OpenAI or Azure OpenAI API key. <a href="https://platform.openai.com/account/api-keys" target="_blank">Click here for OpenAI key</a>.';
$string['model'] = 'Model';
$string['model_desc'] = 'Enter the model name (e.g., gpt-4).';
$string['ai_not_configured'] = 'AI integration is active but settings are incomplete.';
$string['ai_failed'] = 'AI request failed.';
$string['ai_system_prompt'] = 'You are an educational assistant. Provide motivational and pedagogical feedback for students or schools.';
$string['ai_prompt_student'] = 'Write a short pedagogical analysis for the student based on the following competency percentages:';
$string['ai_prompt_school'] = 'Write a pedagogical analysis and strategy for the school based on the following competency percentages:';

// Comments & Feedback.
$string['comment'] = 'Comment';
$string['generalcomment'] = 'General Comment';
$string['comment_red'] = 'Topics not yet achieved: {$a}';
$string['comment_orange'] = 'Partially learned topics: {$a}';
$string['comment_blue'] = 'Mostly learned topics: {$a}';
$string['comment_green'] = 'Fully learned topics: {$a}';

// Structured Feedback.
$string['structured_red'] = '{$a->shortname}: Success rate %{$a->rate}. Not enough progress yet. Recommendation: review and use extra resources.';
$string['structured_orange'] = '{$a->shortname}: Success rate %{$a->rate}. Partially learned. Recommendation: practice more sample questions.';
$string['structured_blue'] = '{$a->shortname}: Success rate %{$a->rate}. Mostly learned. Recommendation: reinforce missing points.';
$string['structured_green'] = '{$a->shortname}: Success rate %{$a->rate}. Fully learned. Recommendation: move to advanced activities.';

// Timeline.
$string['timeline'] = 'Timeline';
$string['timelineheading'] = 'Competency Progress Over Time';
$string['filterlabel'] = 'Filter';
$string['last30days'] = 'Last 30 days';
$string['last90days'] = 'Last 90 days';
$string['alltime'] = 'All time';

// Admin Settings.
$string['maxrows'] = 'Maximum rows';
$string['maxrows_desc'] = 'Maximum number of rows to display in tables.';
$string['success_threshold'] = 'Success threshold';
$string['success_threshold_desc'] = 'Default success percentage for color coding.';
