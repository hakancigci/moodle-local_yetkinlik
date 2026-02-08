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
 * Report for competency.
 *
 * @package   local_yetkinlik
 * @copyright 2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later*/

$string['pluginname']      = 'Competency Plugin';
$string['classreport']     = 'Class Report';
$string['pdfreport']       = '📄 PDF Report';
$string['user']            = 'Student';
$string['competency']      = 'Competency';
$string['allusers']        = 'All students';
$string['student']        = 'Student';
$string['allcompetencies'] = 'All competencies';
$string['show']            = 'Show';
$string['courseavg']       = 'Course Avg.';
$string['classavg']        = 'Class Avg.';
$string['studentavg']      = 'Student';
$string['recordupdated']   = 'Record updated successfully';
$string['savechanges']     = 'Save changes';
$string['evidence']        = 'Evidence';

$string['teacherstudentcompetency'] = 'Student Competency Analysis';
$string['selectstudent']            = 'Select student';
$string['selectcompetency']         = 'Select competency';
$string['quiz']                     = 'Quiz';
$string['question']                 = 'Question';
$string['correct']                  = 'Correct';
$string['success']                  = 'Success';
$string['total']                    = 'TOTAL';
$string['nodatastudentcompetency']  = 'No quiz data found for this student in this competency.';

$string['studentclass']    = 'Competency Analysis';
$string['studentreport']   = 'My Competency Report';
$string['competencycode']  = 'Competency Code';
$string['questioncount']   = 'Number of Questions';
$string['correctcount']    = 'Number of Correct';
$string['successrate']     = 'Success Rate';
$string['pdfmystudent']    = '📄 View My PDF Report';
$string['comment']         = 'Comment';
$string['studentpdfreport']= 'Competency Report';

$string['generalcomment']  = 'General Comment:';
$string['colorlegend']     = 'Color Legend:';
$string['redlegend']       = 'Red: Not achieved (0–39%)';
$string['orangelegend']    = 'Orange: Partially achieved (40–59%)';
$string['bluelegend']      = 'Blue: Mostly achieved (60–79%)';
$string['greenlegend']     = 'Green: Fully achieved (80%+)';

$string['studentexam']     = 'My Exam Competency Analysis';
$string['selectquiz']      = 'Select exam';
$string['successpercent']  = 'Success %';
$string['noexamdata']      = 'No competency data found for this exam.';

$string['studentcompetencyexams'] = 'My Competency-Based Exam Analysis';
$string['nocompetencyexamdata']   = 'No exam data found for this competency.';

$string['groupcompetency']        = 'Group Competency Analysis';
$string['selectgroup']            = 'Select group';
$string['studentcompetencydetail']= 'Student Competency Detail';
$string['groupquizcompetency']    = 'Group Quiz Competency Analysis';

$string['maxrows']                = 'Maximum rows';
$string['maxrows_desc']           = 'Maximum number of rows to display in tables';
$string['success_threshold']      = 'Success threshold';
$string['success_threshold_desc'] = 'Default success percentage for color coding';

$string['enable_ai']        = 'Enable AI integration';
$string['enable_ai_desc']   = 'Enable AI-based pedagogical comments. API key and model selection are taken from Moodle core.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter your OpenAI or Azure OpenAI API key. 
<a href="https://platform.openai.com/account/api-keys" target="_blank">Click here for OpenAI key</a> 
or 
<a href="https://portal.azure.com/" target="_blank">Click here for Azure OpenAI key</a>.';
$string['model']            = 'Model';
$string['model_desc']       = 'Enter the model name to use (e.g., gpt-4).';
$string['ai_not_configured']= 'AI integration is active but the plugin settings for API key or model are not configured.';

$string['schoolpdfreport']  = 'School General Achievement Report';
$string['schoolreport']     = 'School General Report';
$string['schoolpdf']        = 'School PDF Report';

$string['timeline']         = 'Timeline';
$string['timelineheading']  = 'Competency Progress Over Time';
$string['filterlabel']      = 'Filter';
$string['last30days']       = 'Last 30 days';
$string['last90days']       = 'Last 90 days';
$string['alltime']          = 'All time';
$string['successrate']      = 'Success Rate (%)';
$string['generalcomment'] = 'General comment';
$string['comment_red'] = 'Topics not yet achieved: {$a}';
$string['comment_orange'] = 'Partially learned topics: {$a}';
$string['comment_blue'] = 'Mostly learned topics: {$a}';
$string['comment_green'] = 'Fully learned topics: {$a}';

$string['ai_not_configured'] = 'AI is not configured.';
$string['ai_prompt_student'] = 'Write a short pedagogical analysis for the student based on the following competency percentages:';
$string['ai_prompt_school'] = 'Write a pedagogical analysis and development strategy for the school based on the following competency percentages:';
$string['ai_system_prompt'] = 'You are an educational assistant. Provide motivational and pedagogical feedback for students or schools.';
$string['ai_failed'] = 'AI request failed.';

$string['structured_red'] = '{$a->shortname}: Success rate %{$a->rate}. Not enough progress yet. Recommendation: review, use extra resources, and ask your teacher.';
$string['structured_orange'] = '{$a->shortname}: Success rate %{$a->rate}. Partially learned. Recommendation: practice more, solve sample questions, and consolidate knowledge.';
$string['structured_blue'] = '{$a->shortname}: Success rate %{$a->rate}. Mostly learned. Recommendation: reinforce with repetition and fill in missing points.';
$string['structured_green'] = '{$a->shortname}: Success rate %{$a->rate}. Fully learned. Recommendation: move to advanced activities and apply knowledge in different contexts.';
$string['privacy:metadata'] = 'The Yetkinlik plugin does not store any personal data.';

