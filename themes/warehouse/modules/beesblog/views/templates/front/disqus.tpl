{if isset($disqusUsername) && $disqusUsername}
    <div id="disqus_thread" class="post-block"></div>
    <script>
        var disqus_config = function () {
            this.page.url = '{$postPath|escape:'javascript':'UTF-8'}';
            this.page.identifier = '{$post->id|intval}';
        };
        (function () {
            var d = document, s = d.createElement('script');
            s.src = '//{$disqusUsername|escape:'javascript':'UTF-8'}.disqus.com/embed.js';
            s.setAttribute('data-timestamp', +new Date());
            (d.head || d.body).appendChild(s);
        })();
    </script>
    <noscript>Please enable JavaScript to view comments powered by Disqus.</noscript>
{/if}
