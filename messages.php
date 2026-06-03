<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();
$error   = '';

// --- BADGE ONLY MODE ---
if (isset($_GET['badge_only'])) {
    $count = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM messages WHERE receiver_id=$user_id AND is_read=0"
    ))['n'];
    echo (int)$count;
    exit();
}

// --- FETCH ONLY MODE (live message polling) ---
if (isset($_GET['fetch_only']) && isset($_GET['product_id']) && isset($_GET['with'])) {
    $pid      = (int)$_GET['product_id'];
    $other_id = (int)$_GET['with'];

    mysqli_query($conn, "
        UPDATE messages SET is_read = 1
        WHERE product_id=$pid AND sender_id=$other_id AND receiver_id=$user_id
    ");

    $result = mysqli_query($conn, "
        SELECT m.*, u.username AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.product_id = $pid
          AND (
              (m.sender_id = $user_id AND m.receiver_id = $other_id) OR
              (m.sender_id = $other_id AND m.receiver_id = $user_id)
          )
        ORDER BY m.sent_at ASC
    ");

    $output = '';
    $found  = false;

    while ($msg = mysqli_fetch_assoc($result)) {
        $found   = true;
        $is_mine = $msg['sender_id'] === $user_id;
        $class   = $is_mine ? 'msg-mine' : 'msg-theirs';
        $text    = nl2br(htmlspecialchars($msg['message']));
        $time    = date('d M, H:i', strtotime($msg['sent_at']));
        $output .= "<div class='msg-bubble $class'>
                        <div class='msg-text'>$text</div>
                        <div class='msg-time'>$time</div>
                    </div>";
    }

    if (!$found) {
        $output = "<div class='thread-empty'>No messages yet — say something.</div>";
    }

    echo $output;
    exit();
}

// --- SEND A MESSAGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $product_id  = (int)$_POST['product_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $message     = trim(mysqli_real_escape_string($conn, $_POST['message']));

    if (!$message) {
        $error = "Message cannot be empty.";
    } elseif ($receiver_id === $user_id) {
        $error = "You cannot message yourself.";
    } else {
        mysqli_query($conn, "
            INSERT INTO messages (product_id, sender_id, receiver_id, message)
            VALUES ($product_id, $user_id, $receiver_id, '$message')
        ");
        header("Location: messages.php?product_id=$product_id&with=$receiver_id");
        exit();
    }
}

// --- MARK AS READ ---
if (isset($_GET['product_id']) && isset($_GET['with'])) {
    $pid      = (int)$_GET['product_id'];
    $other_id = (int)$_GET['with'];
    mysqli_query($conn, "
        UPDATE messages SET is_read = 1
        WHERE product_id=$pid AND sender_id=$other_id AND receiver_id=$user_id
    ");
}

// --- LOAD CONVERSATIONS ---
$conversations = mysqli_query($conn, "
    SELECT
        p.id AS product_id,
        p.title AS product_title,
        p.is_sold,
        CASE WHEN m.sender_id = $user_id THEN m.receiver_id ELSE m.sender_id END AS other_id,
        u.username AS other_username,
        MAX(m.sent_at) AS last_message_time,
        SUM(CASE WHEN m.receiver_id = $user_id AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN products p ON m.product_id = p.id
    JOIN users u ON u.id = CASE WHEN m.sender_id = $user_id THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    GROUP BY p.id, other_id
    ORDER BY last_message_time DESC
");

// --- LOAD ACTIVE THREAD ---
$thread         = [];
$active_product = null;
$active_other   = null;

if (isset($_GET['product_id']) && isset($_GET['with'])) {
    $pid      = (int)$_GET['product_id'];
    $other_id = (int)$_GET['with'];

    $active_product = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.*, p.user_id AS seller_id, u.username AS seller_name
         FROM products p JOIN users u ON p.user_id = u.id
         WHERE p.id = $pid"
    ));

    $active_other = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id, username FROM users WHERE id = $other_id"
    ));

    $result = mysqli_query($conn, "
        SELECT m.*, u.username AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.product_id = $pid
          AND (
              (m.sender_id = $user_id AND m.receiver_id = $other_id) OR
              (m.sender_id = $other_id AND m.receiver_id = $user_id)
          )
        ORDER BY m.sent_at ASC
    ");

    while ($row = mysqli_fetch_assoc($result)) {
        $thread[] = $row;
    }
}

$page_title = 'Messages';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Messages</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="messages-layout">

        <!-- LEFT: Conversation list -->
        <div class="conversation-list">
            <div class="conv-list-header">Conversations</div>

            <?php if (mysqli_num_rows($conversations) === 0): ?>
                <div style="padding:20px 16px;font-size:13px;color:var(--gray-400);line-height:1.6;">
                    No conversations yet.<br>Start one from any listing page.
                </div>
            <?php endif; ?>

            <?php while ($conv = mysqli_fetch_assoc($conversations)): ?>
                <?php
                $is_active = isset($_GET['product_id']) && isset($_GET['with'])
                    && (int)$_GET['product_id'] === $conv['product_id']
                    && (int)$_GET['with']       === $conv['other_id'];
                ?>
                <a href="messages.php?product_id=<?= $conv['product_id'] ?>&with=<?= $conv['other_id'] ?>"
                   class="conv-item <?= $is_active ? 'conv-active' : '' ?>">
                    <div class="conv-name">
                        <?= htmlspecialchars($conv['other_username']) ?>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="conv-badge"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="conv-product">
                        <?= htmlspecialchars($conv['product_title']) ?>
                        <?php if ($conv['is_sold']): ?>
                            <span style="color:var(--red);font-weight:600;"> · Sold</span>
                        <?php endif; ?>
                    </div>
                    <div class="conv-time"><?= date('d M, H:i', strtotime($conv['last_message_time'])) ?></div>
                </a>
            <?php endwhile; ?>
        </div>

        <!-- RIGHT: Message thread -->
        <div class="message-thread">

            <?php if ($active_product && $active_other): ?>

                <!-- Thread header: who you're talking to + item info -->
                <div class="thread-header">
                    <div>
                        <div class="thread-header-title">
                            <?= htmlspecialchars($active_other['username']) ?>
                        </div>
                        <div class="thread-header-sub">
                            <a href="product-details.php?id=<?= $active_product['id'] ?>">
                                <?= htmlspecialchars($active_product['title']) ?>
                            </a>
                            &middot; R <?= number_format($active_product['price'], 2) ?>
                            &middot; Seller:
                            <a href="profile.php?id=<?= $active_product['seller_id'] ?>">
                                <?= htmlspecialchars($active_product['seller_name']) ?>
                            </a>
                            <?php if ($active_product['is_sold']): ?>
                                <span style="color:var(--red);font-weight:600;"> · Sold</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($active_product['seller_id'] === $user_id && !$active_product['is_sold']): ?>
                        <a href="mark-sold.php?id=<?= $active_product['id'] ?>&redirect=messages.php?product_id=<?= $active_product['id'] ?>&with=<?= $active_other['id'] ?>"
                           class="btn btn-yellow"
                           style="font-size:13px;padding:7px 14px;"
                           onclick="return confirm('Mark this item as sold?')">
                            Mark as Sold
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Message bubbles -->
                <div class="thread-messages" id="thread-messages">
                    <?php if (empty($thread)): ?>
                        <div class="thread-empty">No messages yet — say something.</div>
                    <?php endif; ?>

                    <?php foreach ($thread as $msg): ?>
                        <?php $is_mine = $msg['sender_id'] === $user_id; ?>
                        <div class="msg-bubble <?= $is_mine ? 'msg-mine' : 'msg-theirs' ?>">
                            <div class="msg-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="msg-time"><?= date('d M, H:i', strtotime($msg['sent_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!$active_product['is_sold']): ?>
                    <div class="thread-safety-note">
                        Stay safe — avoid sharing personal details. Meet in a public place.
                    </div>
                    <form method="POST"
                          action="messages.php?product_id=<?= $active_product['id'] ?>&with=<?= $active_other['id'] ?>"
                          class="reply-form">
                        <input type="hidden" name="product_id"  value="<?= $active_product['id'] ?>">
                        <input type="hidden" name="receiver_id" value="<?= $active_other['id'] ?>">
                        <input type="hidden" name="send_message" value="1">
                        <textarea name="message" id="message-input"
                                  placeholder="Type a message..." required></textarea>
                        <button type="submit" class="btn btn-green">Send</button>
                    </form>
                <?php else: ?>
                    <div style="padding:14px 20px;">
                        <div class="alert alert-info">
                            This item has been marked as sold. No further messages can be sent.
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-conv-selected">
                    Select a conversation from the left to view messages.
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<?php if ($active_product && $active_other): ?>
<script>
var productId = <?= $active_product['id'] ?>;
var otherId   = <?= $active_other['id'] ?>;

var threadDiv = document.getElementById('thread-messages');
if (threadDiv) threadDiv.scrollTop = threadDiv.scrollHeight;

function loadNewMessages() {
    var url = 'messages.php?product_id=' + productId + '&with=' + otherId + '&fetch_only=1';
    fetch(url)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            var thread = document.getElementById('thread-messages');
            if (!thread) return;
            if (thread.innerHTML.trim() !== html.trim()) {
                var atBottom = thread.scrollHeight - thread.scrollTop <= thread.clientHeight + 50;
                thread.innerHTML = html;
                if (atBottom) thread.scrollTop = thread.scrollHeight;
            }
        });
}

setInterval(loadNewMessages, 5000);
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
