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
 * Turkish strings for local_yetkinlik plugin.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Genel Dizgiler.
$string['pluginname'] = 'Yetkinlik Analiz Sistemi';
$string['privacy:metadata'] = 'Yetkinlik eklentisi herhangi bir kişisel veri depolamaz.';
$string['show'] = 'Göster';
$string['savechanges'] = 'Değişiklikleri Kaydet';
$string['recordupdated'] = 'Kayıt başarıyla güncellendi';

// Navigasyon ve Roller.
$string['user'] = 'Öğrenci';
$string['student'] = 'Öğrenci';
$string['allusers'] = 'Tüm Öğrenciler';
$string['competency'] = 'Yetkinlik / Kazanım';
$string['allcompetencies'] = 'Tüm Yetkinlikler';
$string['competencycode'] = 'Yetkinlik Kodu';

// Genel Raporlar.
$string['studentanalysis'] = 'Öğrenci Analizi';
$string['classreport'] = 'Sınıf Kazanım Raporu';
$string['pdfreport'] = '📄 PDF Raporu';
$string['courseavg'] = 'Kurs Ort.';
$string['classavg'] = 'Sınıf Ort.';
$string['studentavg'] = 'Öğrenci Ort.';
$string['evidence'] = 'Kanıt';
$string['success'] = 'Başarı';
$string['total'] = 'TOPLAM';
$string['quiz'] = 'Sınav';
$string['question'] = 'Soru';
$string['correct'] = 'Doğru';

// Öğretmen/Öğrenci Yetkinlik Analizi.
$string['teacherstudentcompetency'] = 'Öğrenci Yetkinlik Analizi';
$string['selectstudent'] = 'Öğrenci seçiniz';
$string['selectcompetency'] = 'Yetkinlik seçiniz';
$string['nodatastudentcompetency'] = 'Bu öğrenci için bu yetkinlikte sınav verisi bulunamadı.';
$string['studentcompetencydetail'] = 'Öğrenci Yetkinlik Detayı';

// Karnem ve Öğrenci Görünümü.
$string['studentclass'] = 'Yetkinlik Durumu';
$string['studentreport'] = 'Yetkinlik Karnem';
$string['myreportcard'] = 'Karnem';
$string['myexamanalysis'] = 'Sınav Kazanım Analizim';
$string['mycompetencyexams'] = 'Yetkinlik Bazlı Sınavlarım';
$string['mycompetencystate'] = 'Yetkinlik Durumu';
$string['mycompetencies'] = 'Kazanım Analizlerim';
$string['questioncount'] = 'Soru Sayısı';
$string['correctcount'] = 'Doğru Sayısı';
$string['successrate'] = 'Başarı Oranı (%)';
$string['pdfmystudent'] = '📄 PDF Karnemi Görüntüle';
$string['studentpdfreport'] = 'Yetkinlik Gelişim Raporu';
$string['studentanalysis'] = 'Kazanım Karşılaştırma Raporum';
$string['analysisfor'] = 'Kazanım Analizim: {$a}';
$string['compareinfo'] = 'Bu raporda kendi başarınızı, kursun geneli ve sınıfınızla kıyaslayabilirsiniz.';
$string['classinfo'] = 'Sınıf: {$a}';
$string['studentanalysis'] = 'Kazanım Karşılaştırma Raporum'; 
$string['analysisfor'] = 'Kazanım Analizim: {$a}'; 
$string['compareinfo'] = 'Bu raporda kendi başarınızı, kursun geneli ve sınıfınızla kıyaslayabilirsiniz.'; 
$string['classinfo'] = 'Sınıf: {$a}'; 
$string['competencyname'] = 'Kazanım / Yetkinlik'; 
$string['courseavg'] = 'Kurs Ortalaması'; 
$string['classavg'] = 'Sınıf Ortalaması'; 
$string['myavg'] = 'Benim Başarım'; 
$string['nodatafound'] = 'Bu kursta henüz analiz edilecek tamamlanmış sınav verisi bulunamadı.'; 
$string['error_no_enrolment'] = 'Bu kursa kayıtlı olmadığınız için raporu görüntüleyemezsiniz.';

// Renk Açıklamaları.
$string['colorlegend'] = 'Renk Anahtarı:';
$string['redlegend'] = 'Kırmızı: Kazanılmadı (%0–39)';
$string['orangelegend'] = 'Turuncu: Kısmen Kazanıldı (%40–59)';
$string['bluelegend'] = 'Mavi: Büyük Oranda Kazanıldı (%60–79)';
$string['greenlegend'] = 'Yeşil: Tam Kazanıldı (%80+)';

// Sınav Analizi.
$string['studentexam'] = 'Sınav Kazanım Analizim';
$string['selectquiz'] = 'Sınav seçiniz';
$string['successpercent'] = 'Başarı Yüzdesi';
$string['noexamdata'] = 'Bu sınav için yetkinlik verisi bulunamadı.';

// Yetkinlik Bazlı Sınavlar.
$string['studentcompetencyexams'] = 'Yetkinlik Bazlı Sınav Analizim';
$string['nocompetencyexamdata'] = 'Bu yetkinlik için sınav verisi bulunamadı.';

// Grup ve Okul.
$string['groupcompetency'] = 'Grup Yetkinlik Analizi';
$string['selectgroup'] = 'Grup seçiniz';
$string['groupquizcompetency'] = 'Grup Sınav Yetkinlik Analizi';
$string['schoolpdfreport'] = 'Okul Genel Başarı Raporu';
$string['schoolreport'] = 'Okul Genel Raporu';
$string['schoolpdf'] = 'Okul PDF Raporu';

// Yapay Zeka (AI) Entegrasyonu.
$string['enable_ai'] = 'Yapay Zeka Entegrasyonunu Aktif Et';
$string['enable_ai_desc'] = 'Yapay zeka tabanlı pedagojik yorumları aktif eder. API anahtarı ve model seçimi aşağıdan yapılmalıdır.';
$string['apikey'] = 'API Anahtarı';
$string['apikey_desc'] = 'OpenAI veya Azure OpenAI API anahtarınızı girin. <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI anahtarı için tıklayın</a>.';
$string['model'] = 'Model';
$string['model_desc'] = 'Kullanılacak model adını girin (Örn: gpt-4).';
$string['ai_not_configured'] = 'Yapay zeka aktif ancak ayarlar eksik.';
$string['ai_failed'] = 'Yapay zeka isteği başarısız oldu.';
$string['ai_system_prompt'] = 'Siz bir eğitim asistanısınız. Öğrenciler veya okullar için motivasyonel ve pedagojik geri bildirimler sağlayın.';
$string['ai_prompt_student'] = 'Aşağıdaki yetkinlik yüzdelerine dayanarak öğrenci için kısa bir pedagojik analiz yazın:';
$string['ai_prompt_school'] = 'Aşağıdaki yetkinlik yüzdelerine dayanarak okul için bir pedagojik analiz ve gelişim stratejisi yazın:';

// Yorumlar ve Geri Bildirim.
$string['comment'] = 'Yorum';
$string['generalcomment'] = 'Genel Yorum';
$string['comment_red'] = 'Henüz kazanılamayan konular: {$a}';
$string['comment_orange'] = 'Kısmen öğrenilen konular: {$a}';
$string['comment_blue'] = 'Büyük oranda öğrenilen konular: {$a}';
$string['comment_green'] = 'Tam öğrenilen konular: {$a}';

// Yapılandırılmış Geri Bildirimler.
$string['structured_red'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Henüz yeterli gelişim sağlanamadı. Öneri: Konuyu tekrar et ve ek kaynaklardan yararlan.';
$string['structured_orange'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Kısmen öğrenildi. Öneri: Daha fazla örnek soru çözerek pekiştir.';
$string['structured_blue'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Büyük oranda öğrenildi. Öneri: Eksik kalan noktaları gözden geçir.';
$string['structured_green'] = '{$a->shortname}: Başarı oranı %{$a->rate}. Tam başarı sağlandı. Öneri: İleri düzey etkinliklere geçebilirsin.';

// Zaman Çizelgesi (Timeline).
$string['timeline'] = 'Zaman Çizelgesi';
$string['timelineheading'] = 'Zaman İçinde Yetkinlik Gelişimi';
$string['filterlabel'] = 'Filtrele';
$string['last30days'] = 'Son 30 gün';
$string['last90days'] = 'Son 90 gün';
$string['alltime'] = 'Tüm zamanlar';

// Yönetici Ayarları.
$string['maxrows'] = 'Maksimum satır';
$string['maxrows_desc'] = 'Tablolarda görüntülenecek maksimum satır sayısı.';
$string['success_threshold'] = 'Başarı eşiği';
$string['success_threshold_desc'] = 'Renk kodlaması için varsayılan başarı yüzdesi.';
