<?php
// add_to_cart.php
session_start();
require_once '../includes/db_connect.php';

// ૧. જો યુઝર લોગ-ઈન ન હોય, તો તેને પહેલા લોગીન કરવા માટે પોપ-અપ બતાવો
if (!isset($_SESSION['user_id'])) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Please Login First',
            text: 'You need to login to your account to add items to the cart.',
            confirmButtonColor: '#0A192F',
            confirmButtonText: 'Go to Login'
        }).then(() => {
            window.location.href = '../auth/login.php';
        });
    </script>
    </body>
    </html>";
    exit;
}

// ૨. URL માંથી પ્રોડક્ટ આઈડી મેળવો
$product_id = isset($_GET['pid']) ? $_GET['pid'] : 0;
$user_id = $_SESSION['user_id'];
$quantity = 1; // ડિફોલ્ટ એક આઈટમ ઉમેરાશે

if ($product_id > 0) {
    try {
        // ૩. ચેક કરો કે આ પ્રોડક્ટ આ યુઝરે કાર્ટમાં પહેલેથી ઉમેરેલી છે કે નહીં
        $check_sql = "SELECT * FROM carts WHERE user_id = :u_id AND product_id = :pid";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':u_id' => $user_id, ':pid' => $product_id]);
        $cart_item = $check_stmt->fetch();

        if ($cart_item) {
            // જો પહેલેથી હોય, તો ફક્ત ક્વોન્ટિટી +1 કરી દો
            $update_sql = "UPDATE carts SET quantity = quantity + 1 WHERE cart_id = :cart_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([':cart_id' => $cart_item['cart_id']]);
        } else {
            // જો પહેલેથી ના હોય, તો નવી લાઈન એડ કરો
            $insert_sql = "INSERT INTO carts (user_id, product_id, quantity) VALUES (:u_id, :pid, :quantity)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                ':u_id' => $user_id,
                ':pid' => $product_id,
                ':quantity' => $quantity
            ]);
        }

        // ૪. સફળતાનું સ્મૂધ SaaS-style પેજ (SweetAlert ની જગ્યાએ)
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <title>Added to Cart</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }

            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                background: #f3ede4;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .card {
                background: #faf6ef;
                border-radius: 24px;
                max-width: 620px;
                width: 100%;
                padding: 56px 40px 40px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            }

            .icon-wrap {
                position: relative;
                width: 220px;
                height: 220px;
                margin: 0 auto 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .glow {
                position: absolute;
                width: 200px;
                height: 200px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(212,163,58,0.25) 0%, rgba(212,163,58,0) 70%);
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); opacity: 0.8; }
                50% { transform: scale(1.12); opacity: 1; }
            }

            .icon-circle {
                position: relative;
                width: 150px;
                height: 150px;
                border-radius: 50%;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 24px rgba(212,163,58,0.25);
                animation: pop 0.5s cubic-bezier(0.34,1.56,0.64,1);
            }

            @keyframes pop {
                0% { transform: scale(0); }
                100% { transform: scale(1); }
            }

            .icon-circle svg {
                width: 68px;
                height: 68px;
            }

            .checkmark {
                stroke-dasharray: 40;
                stroke-dashoffset: 40;
                animation: draw 0.5s 0.4s ease forwards;
            }

            @keyframes draw {
                to { stroke-dashoffset: 0; }
            }

            h1 {
                font-family: Georgia, 'Times New Roman', serif;
                color: #0a192f;
                font-size: 34px;
                margin-bottom: 14px;
            }

            p.subtitle {
                color: #55627a;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 28px;
            }

            .redirect-box {
                background: #efe7d8;
                border-radius: 14px;
                padding: 16px 24px;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-size: 15px;
                color: #0a192f;
                margin-bottom: 32px;
            }

            .redirect-box .spinner {
                width: 16px;
                height: 16px;
                border: 2px solid #d4a33a;
                border-top-color: transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            .redirect-box .count {
                color: #d4a33a;
                font-weight: 700;
                font-size: 17px;
                min-width: 18px;
                display: inline-block;
            }

            .buttons {
                display: flex;
                gap: 14px;
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn {
                padding: 12px 26px;
                border-radius: 10px;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                border: none;
                transition: transform 0.15s ease, opacity 0.15s ease;
            }

            .btn:hover { transform: translateY(-2px); }

            .btn-primary {
                background: #0a192f;
                color: #fff;
            }

            .btn-secondary {
                background: transparent;
                color: #0a192f;
                border: 1.5px solid #d8cdb8;
            }

            /* ===== Mobile responsive ===== */
            @media (max-width: 600px) {
                body {
                    padding: 0;
                    align-items: flex-start;
                }

                .card {
                    max-width: 100%;
                    width: 100%;
                    min-height: 100vh;
                    border-radius: 0;
                    padding: 60px 24px 40px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }

                .icon-wrap {
                    width: 260px;
                    height: 260px;
                    margin: 0 auto 36px;
                }

                .glow {
                    width: 240px;
                    height: 240px;
                }

                .icon-circle {
                    width: 180px;
                    height: 180px;
                }

                .icon-circle svg {
                    width: 84px;
                    height: 84px;
                }

                h1 {
                    font-size: 30px;
                    line-height: 1.3;
                }

                p.subtitle {
                    font-size: 17px;
                }

                .redirect-box {
                    width: 100%;
                    justify-content: center;
                    font-size: 16px;
                    padding: 18px 20px;
                }

                .redirect-box .count {
                    font-size: 19px;
                }

                .buttons {
                    flex-direction: column;
                    width: 100%;
                }

                .btn {
                    width: 100%;
                    padding: 16px 26px;
                    font-size: 17px;
                }
            }

            @media (max-width: 380px) {
                .icon-wrap {
                    width: 220px;
                    height: 220px;
                }
                .glow {
                    width: 200px;
                    height: 200px;
                }
                .icon-circle {
                    width: 150px;
                    height: 150px;
                }
                h1 {
                    font-size: 26px;
                }
            }
        </style>
        </head>
        <body>

        <div class="card">
            <div class="icon-wrap">
                <div class="glow"></div>
                <div class="icon-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#d4a33a" stroke-width="1.5">
                        <path d="M6 8h12l-1 12H7L6 8z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 8V6a3 3 0 0 1 6 0v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path class="checkmark" d="M9 13l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <h1>Your item is added to cart!</h1>
            <p class="subtitle">Great choice! Your selected item has been successfully added to your cart.</p>

            <div class="redirect-box">
                <span class="spinner"></span>
                Redirecting you to home in <span class="count" id="count">3</span> seconds...
            </div>

            <div class="buttons">
                <a href="cart.php" class="btn btn-primary">View Cart</a>
                <a href="index.php" class="btn btn-secondary" id="cancelBtn">Continue Shopping</a>
            </div>
        </div>

        <script>
            // Page load hote hi dono start: countdown display + actual redirect timer
            const TOTAL_SECONDS = 3;
            let seconds = TOTAL_SECONDS;
            const countEl = document.getElementById('count');

            // Countdown number har 1 second update hoga
            const countdownTimer = setInterval(() => {
                seconds--;
                if (seconds > 0) {
                    countEl.textContent = seconds;
                } else {
                    clearInterval(countdownTimer);
                }
            }, 1000);

            // Exact TOTAL_SECONDS baad redirect (page load se hi start, koi drift nahi)
            const redirectTimer = setTimeout(() => {
                window.location.href = '../index.php';
            }, TOTAL_SECONDS * 100);

            // User agar manually button dabaye to auto-redirect cancel
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    clearInterval(countdownTimer);
                    clearTimeout(redirectTimer);
                });
            });
        </script>

        </body>
        </html>
        <?php

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit;
}
