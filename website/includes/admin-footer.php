    </main>
</div>
<script>window.HIDDEN_GEMS = {baseUrl: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrf_token()) ?>};</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
