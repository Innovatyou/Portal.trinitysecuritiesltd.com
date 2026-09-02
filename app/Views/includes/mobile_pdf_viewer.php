<div class="app-modal">
    <div class="app-modal-content">
        <style>
            #pdf-container {
                min-height: 100%;
            }

            #pdf-container canvas {
                display: block;
                max-width: 100%;
                height: auto;
            }
        </style>

        <div id="pdf-container"></div>

        <script src="<?php echo base_url('assets/js/pdf-js/pdf.min.js'); ?>"></script>
        <script>
            var url = <?php echo json_encode($pdf_path); ?>;
            var pdfjsLib = window['pdfjs-dist/build/pdf'];

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                '<?php echo base_url('assets/js/pdf-js/pdf.worker.min.js'); ?>';

            var container = document.getElementById('pdf-container');

            // IMPORTANT: clear old content every time
            container.innerHTML = '';

            pdfjsLib.getDocument(url).promise.then(function(pdf) {

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    pdf.getPage(pageNum).then(function(page) {

                        var viewport = page.getViewport({
                            scale: 2
                        });

                        var canvas = document.createElement('canvas');
                        var context = canvas.getContext('2d');

                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        lastScrollTop = $(window).scrollTop();
                        $('.page-container').addClass('d-none');

                        container.appendChild(canvas);

                        page.render({
                            canvasContext: context,
                            viewport: viewport
                        });
                    });
                }

            }).catch(function(error) {
                console.error('PDF load error:', error);
            });

            var lastScrollTop = 0;
            $(document).on('click', '.app-modal-fixed-close-button', function() {
                $('.page-container').removeClass('d-none');

                setTimeout(function() {
                    $(window).scrollTop(lastScrollTop);
                }, 50);
            });
        </script>
    </div>
</div>