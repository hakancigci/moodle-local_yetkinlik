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

$string['pluginname']      = 'Yetkinlik Yönetimi';
$string['classreport']     = 'Sınıf Raporu';
$string['pdfreport']       = 'PDF Raporu';
$string['user']            = 'Öğrenci';
$string['competency']      = 'Kazanım';
$string['allusers']        = 'Tüm öğrenciler';
$string['student']        = 'Öğrenci';
$string['allcompetencies'] = 'Tüm kazanımlar';
$string['show']            = 'Göster';
$string['courseavg']       = 'Kurs Ort.';
$string['classavg']        = 'Sınıf Ort.';
$string['studentavg']      = 'Öğrenci';
$string['recordupdated']   = 'Kayıt başarıyla güncellendi';
$string['savechanges']     = 'Değişiklikleri kaydet';
$string['evidence']        = 'Kanıt';

$string['teacherstudentcompetency'] = 'Öğrenci Kazanım Analizi';
$string['selectstudent']            = 'Öğrenci seç';
$string['selectcompetency']         = 'Kazanım seç';
$string['quiz']                     = 'Sınav';
$string['question']                 = 'Soru';
$string['correct']                  = 'Doğru';
$string['success']                  = 'Başarı';
$string['total']                    = 'TOPLAM';
$string['nodatastudentcompetency']  = 'Bu öğrenci için bu kazanımda sınav verisi bulunamadı.';

$string['studentclass']    = 'Kazanım Analizi';
$string['studentreport']   = 'Kazanım Karnem';
$string['competencycode']  = 'Kazanım Kodu';
$string['questioncount']   = 'Soru Sayısı';
$string['correctcount']    = 'Doğru Sayısı';
$string['successrate']     = 'Başarı Oranı';
$string['pdfmystudent']    = '📄 PDF Raporumu Görüntüle';
$string['comment']         = 'Yorum';
$string['studentpdfreport']= 'Kazanım Raporu';

$string['generalcomment']  = 'Genel Yorum:';
$string['colorlegend']     = 'Renk Açıklamaları:';
$string['redlegend']       = 'Kırmızı: Kazanılmamış (%0–39)';
$string['orangelegend']    = 'Turuncu: Kısmen kazanılmış (%40–59)';
$string['bluelegend']      = 'Mavi: Çoğunlukla kazanılmış (%60–79)';
$string['greenlegend']     = 'Yeşil: Tamamen kazanılmış (%80+)';

$string['studentexam']     = 'Sınav Kazanım Analizim';
$string['selectquiz']      = 'Sınav seçiniz';
$string['successpercent']  = 'Başarı %';
$string['noexamdata']      = 'Bu sınav için kazanım verisi bulunamadı.';

$string['studentcompetencyexams'] = 'Yeterlilik Temelli Sınav Analizim';
$string['nocompetencyexamdata']   = 'Bu kazanım için sınav verisi bulunamadı.';

$string['groupcompetency']        = 'Grup Kazanım Analizi';
$string['selectgroup']            = 'Grup seçiniz';
$string['studentcompetencydetail']= 'Öğrenci Kazanım Detayı';
$string['groupquizcompetency']    = 'Grup Sınav Kazanım Analizi';

$string['maxrows']                = 'Maksimum satır';
$string['maxrows_desc']           = 'Tabloda gösterilecek maksimum satır sayısı';
$string['success_threshold']      = 'Başarı eşiği';
$string['success_threshold_desc'] = 'Renk kodlaması için varsayılan başarı yüzdesi';

$string['enable_ai']        = 'AI entegrasyonunu etkinleştir';
$string['enable_ai_desc']   = 'Bu ayar ile AI entegrasyonunu açıp kapatabilirsin.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter your OpenAI or Azure OpenAI API key. 
<a href="https://platform.openai.com/account/api-keys" target="_blank">Click here for OpenAI key</a> 
or 
<a href="https://portal.azure.com/" target="_blank">Click here for Azure OpenAI key</a>.';
$string['model']            = 'Model';
$string['model_desc']       = 'Kullanılacak model adını girin (örneğin: gpt-4).';
$string['ai_not_configured']= 'AI entegrasyonu aktif ama eklenti ayarlarında API anahtarı veya model yapılandırılmamış.';

$string['schoolpdfreport']  = 'Okul Genel Kazanım Raporu';
$string['schoolreport']     = 'Okul Genel Raporu';
$string['schoolpdf']        = 'Okul PDF Raporu';

$string['timeline']         = 'Zaman Çizelgesi';
$string['timelineheading']  = 'Zaman İçinde Kazanım Gelişimi';
$string['filterlabel']      = 'Filtre';
$string['last30days']       = 'Son 30 gün';
$string['last90days']       = 'Son 90 gün';
$string['alltime']          = 'Tüm zaman';
$string['successrate']      = 'Başarı Oranı (%)';
$string['generalcomment'] = 'Genel yorum';
$string['comment_red'] = 'Henüz kazanmadığın konular: {$a}';
$string['comment_orange'] = 'Kısmen öğrendiğin konular: {$a}';
$string['comment_blue'] = 'Çoğunlukla öğrendiğin konular: {$a}';
$string['comment_green'] = 'Tamamen öğrendiğin konular: {$a}';

$string['ai_not_configured'] = 'Yapay zekâ yapılandırılmamış.';
$string['ai_prompt_student'] = 'Aşağıdaki kazanım yüzdelerine göre öğrenciye kısa pedagojik analiz yaz:';
$string['ai_prompt_school'] = 'Aşağıdaki kazanım yüzdelerine göre okul genelinde pedagojik analiz ve geliştirme stratejisi yaz:';
$string['ai_system_prompt'] = 'Sen bir eğitim asistanısın. Öğrenciye veya okul geneline motive edici ve pedagojik yorumlar ver.';
$string['ai_failed'] = 'Yapay zekâ çağrısı başarısız oldu.';

$string['structured_red'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Bu konuda henüz yeterince ilerleme kaydedilmedi. Önerim: tekrar yap, ek kaynaklardan çalış ve öğretmenine sorularını yönelt.';
$string['structured_orange'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Kısmen öğrenilmiş durumda. Önerim: daha fazla pratik yap, örnek sorular çöz ve bilgini pekiştir.';
$string['structured_blue'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Çoğunlukla öğrenilmiş durumda. Önerim: tekrarlarla bilgini sağlamlaştır, eksik noktaları tamamla.';
$string['structured_green'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Tam öğrenilmiş durumda. Önerim: ileri düzey etkinliklere geç, bilgini farklı durumlarda uygula.';
$string['privacy:metadata'] = 'Yetkinlik eklentisi herhangi bir kişisel veri saklamaz.';
