<?php
require 'config/database.php';
$conn = get_db_connection();

$emails = [
    "faiqbhat123@gmail.con",
    "masrat5qadir@gmail.com",
    "aymenraina@gmail.com",
    "saqibillahi@gmail.com",
    "shifashaber@gmail.com",
    "shahaisha.004@gmail.com",
    "basharathooria@gmail.com",
    "gsajad609@gmail.com",
    "sunsetchalet08@gmail.com",
    "mirsehraan9@gmail.com",
    "ghouleyepatch31@gmail.com",
    "riyanjavaid9@gamil.com",
    "friedmomo.com@gmail.com",
    "shakeebfarhat40@gmail.com",
    "stayhumble7865@gmail.com",
    "nuzeefakhan612@gmail.com",
    "owaisrashad075@gmail.com",
    "rahilmanzoorsangeen@gmail.com",
    "xainabbhat321@gmail.com",
    "bhatbhatfaiq@gmail.com",
    "ahmadsaadtramboo@gmail.com",
    "bhatmehnaz37@gmail.com",
    "aaminaaltaf868@gmail.com",
    "sbhtshbr@gmail.com",
    "hayamalik07886@gmail.com",
    "jannataslam505@gmail.con",
    "ajmeenferoz@gmail.com",
    "ajmeenagway@gmail.com",
    "kashfiyah52@gmail.com",
    "saimawani260@gmail.com",
    "mxargar9@gmail.com",
    "dalpafrheen@gmail.com",
    "badiahussainbhat@gmail.com",
    "ikhlaasmanzoor@gmail.com",
    "insha8105@gmail.com",
    "imaadmanzoor8@gmail.com",
    "shawlyasmeena@gmail.com",
    "my.artgallery52@gmail.com",
    "mirafsha100@gmail.com",
    "hafsabashirhafsa7@gmail.com",
    "hafsahafsa579900@gmail.com",
    "umertariqrather@gmail.com",
    "moinmj7@gmail.com",
    "mariyamushtaqsagar@gmail.com",
    "sheezanfayaz12@gmail.com",
    "sheezanfayaz504@gmail.com",
    "yahyamir352@gmail.com",
    "kmuizz499@gmail.com",
    "basiqrather4@gmail.com",
    "im.inaam.01@gmail.com",
    "toibabhat1234@gmail.com",
    "zahidkhuroo625@gmail.com",
    "rafidjan0@gmail.com",
    "rafidjan@gamil.com",
    "abcd@gamil.com",
    "rehanmajeed318@gmail.com",
    "abrar8173@gmail.com",
    "kamranwani077@gmail.com",
    "taufeeq322ahmad@gmail.com",
    "muhammedumer0007@gmail.com",
    "maribmuzafar1@gmail.com",
    "usmaan826@gmail.com",
    "bilalshah4732@gmail.com",
    "umer.gm.bhat0@gmail.com",
    "abc@gmail.com",
    "faaniqhussain@gmail.com",
    "waniarham007@gmail.com",
    "babaronaq66@gmail.com",
    "maheenbhat557@gmail.com",
    "sumaya@gmail.com",
    "friedmomos.com@gmail.com",
    "waniyamin07@gmail.com",
    "mirmoumin.ahmad@gmail.com",
    "whiteverses7@gmail.com",
    "justttfrankie@gmail.com",
    "xhafsa3@gmail.com",
    "faiqbhat123@gmail.com",
    "arhamchowdary14@gmail.com",
    "lonetalal@gmail.con",
    "faizanbeigh06@gmail.com",
    "fahadtariqbhatt123@gmail.com",
    "qaimqayoom1@gmail.com",
    "wani19aazim@gmail.com",
    "bfuzail606@gmail.com",
    "bhat.faiq10@gmail.com",
    "hire.iry@gmail.com"
];

$inserted = 0;
$skipped = 0;

foreach ($emails as $raw_email) {
    $email = strtolower(trim($raw_email));
    
    // Fix obvious typos
    $email = str_replace('.con', '.com', $email);
    $email = str_replace('@gamil.', '@gmail.', $email);
    
    // Check if exists
    $check = fetch_one("SELECT id FROM newsletter_subscribers WHERE email = ?", [$email]);
    if ($check) {
        $skipped++;
        continue;
    }
    
    // Insert
    $sql = "INSERT INTO newsletter_subscribers (email, is_active, subscribed_at) VALUES (?, 1, NOW())";
    if (execute_query($sql, [$email])) {
        $inserted++;
    }
}

echo "Bulk Import Complete!\n";
echo "Inserted: $inserted\n";
echo "Skipped (Duplicates): $skipped\n";
?>
