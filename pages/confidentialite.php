<?php
require_once __DIR__ . '/../includes/lang.php';

$contenu = [
    'fr' => [
        'title' => 'Politique de confidentialité',
        'intro' => 'Cette page explique quelles données Acheto collecte et comment elles sont utilisées.',
        'sections' => [
            ['Données collectées', 'Lors de l\'inscription : nom, adresse e-mail, téléphone (facultatif), ville et mot de passe. Lors de la publication d\'une annonce : titre, description, prix, ville, photos et numéro de contact. Lors de l\'envoi d\'un message : nom, adresse e-mail et contenu du message.'],
            ['Utilisation des données', 'Ces données servent uniquement à faire fonctionner la plateforme : gérer votre compte, publier et modérer les annonces, et permettre aux acheteurs de contacter les vendeurs.'],
            ['Informations visibles publiquement', 'Une annonce approuvée affiche son titre, sa description, son prix, sa ville, ses photos, le nom du vendeur et le numéro de contact que vous avez indiqué. Votre adresse e-mail n\'est jamais affichée sur l\'annonce.'],
            ['Sécurité', 'Les mots de passe ne sont jamais enregistrés en clair : ils sont transformés par une fonction de hachage sécurisée. Les formulaires sont protégés et les photos envoyées sont vérifiées.'],
            ['Cookies et stockage local', 'Acheto utilise un cookie de session pour vous garder connecté(e). Votre choix de langue est conservé pendant la session, et votre préférence de thème (clair ou sombre) est conservée dans votre navigateur.'],
            ['Vos droits', 'Vous pouvez modifier vos informations depuis la page Profil et supprimer vos annonces depuis « Mes annonces ». Pour supprimer votre compte, contactez-nous via la page Contact.'],
            ['Contact', 'Pour toute question sur vos données personnelles, utilisez la page Contact du site.'],
        ],
    ],
    'ar' => [
        'title' => 'سياسة الخصوصية',
        'intro' => 'توضح هذه الصفحة البيانات التي يجمعها أشيتو وكيفية استعمالها.',
        'sections' => [
            ['البيانات المجمعة', 'عند التسجيل: الاسم والبريد الإلكتروني والهاتف (اختياري) والمدينة وكلمة المرور. عند نشر إعلان: العنوان والوصف والثمن والمدينة والصور ورقم الاتصال. عند إرسال رسالة: الاسم والبريد الإلكتروني ونص الرسالة.'],
            ['استعمال البيانات', 'تُستعمل هذه البيانات فقط لتشغيل المنصة: تدبير حسابك، ونشر الإعلانات ومراجعتها، وتمكين المشترين من التواصل مع البائعين.'],
            ['المعلومات الظاهرة للعموم', 'يعرض الإعلان المقبول عنوانه ووصفه وثمنه ومدينته وصوره واسم البائع ورقم الاتصال الذي حددته. لا يظهر بريدك الإلكتروني أبدا في الإعلان.'],
            ['الأمان', 'لا يتم حفظ كلمات المرور بشكل مقروء أبدا، بل تُحوَّل بدالة تشفير آمنة. النماذج محمية والصور المرسلة تخضع للتحقق.'],
            ['ملفات الارتباط والتخزين المحلي', 'يستعمل أشيتو ملف ارتباط للجلسة لإبقائك متصلا. يُحفظ اختيار اللغة خلال الجلسة، ويُحفظ تفضيل المظهر (فاتح أو داكن) في متصفحك.'],
            ['حقوقك', 'يمكنك تعديل معلوماتك من صفحة الملف الشخصي وحذف إعلاناتك من «إعلاناتي». لحذف حسابك، تواصل معنا عبر صفحة «اتصل بنا».'],
            ['التواصل', 'لأي سؤال حول بياناتك الشخصية، استعمل صفحة «اتصل بنا» في الموقع.'],
        ],
    ],
][$lang];

$page_title = $contenu['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h1 class="h3 fw-bold mb-3"><?= e($contenu['title']) ?></h1>
            <p class="text-muted-ac mb-4"><?= e($contenu['intro']) ?></p>

            <?php foreach ($contenu['sections'] as $i => [$titre, $texte]): ?>
                <section class="mb-4">
                    <h2 class="h5 fw-bold"><?= ($i + 1) ?>. <?= e($titre) ?></h2>
                    <p class="mb-0"><?= e($texte) ?></p>
                </section>
            <?php endforeach; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>