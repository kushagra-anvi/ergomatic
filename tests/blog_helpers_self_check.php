<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$checks = [
    e('<script>alert("x")</script>') === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;',
    preg_match('/^[a-f0-9]{64}$/', csrf_token()) === 1,
    strpos(csrf_field(), 'name="csrf_token"') !== false,
    post_string('missing') === '',
    !admin_logged_in(),
];
$_SESSION['admin_id'] = 7;
$checks[] = admin_logged_in();
$_POST['shape'] = ['unexpected'];
$checks[] = post_string('shape') === '';
$safeArticle = sanitize_article_html('<p style="color:#123456;position:fixed">Hello <a href="javascript:alert(1)">link</a></p><script>alert(1)</script><table><tr><td>Cell</td></tr></table>');
$checks[] = strpos($safeArticle, 'color:#123456') !== false && strpos($safeArticle, 'position') === false;
$checks[] = strpos($safeArticle, 'javascript:') === false && strpos($safeArticle, '<script') === false;
$safeList = sanitize_article_html('<ol><li data-list="bullet">Bullet item</li><li data-list="ordered" onclick="alert(1)">Numbered item</li><li data-list="unsafe">Unsafe value</li></ol>');
$checks[] = substr_count($safeList, 'data-list=') === 2 && strpos($safeList, 'onclick') === false && strpos($safeList, 'data-list="unsafe"') === false;
$checks[] = strpos($safeArticle, '<table>') !== false && strpos($safeArticle, 'Cell') !== false;
$paragraphHtml = plain_text_to_html("First paragraph.\n\nSecond paragraph.");
$checks[] = substr_count($paragraphHtml, '<p>') === 2 && strpos($paragraphHtml, '</p><p>') !== false;
$legacyTable = render_article_body('&lt;table&gt;&lt;tr&gt;&lt;td&gt;Compare&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;');
$checks[] = strpos($legacyTable, '<table>') !== false && strpos($legacyTable, 'Compare') !== false;

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "Blog helper self-check failed.\n");
    exit(1);
}
echo 'Blog helper self-check passed (' . count($checks) . " checks).\n";
