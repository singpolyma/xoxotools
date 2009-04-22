<h2>Microformats Proxy</h2>
<p>This is a tool that takes a URL which may or may not have microformats markup on it, and outputs a well-formed page with microformats markup on all applicable data.  The detection of this data is community-built using XPath.</p>

<h3>Currently</h3>
<p>Preserves existing hCards and XOXO on a page.</p>
<p>Allows XPath to add hCards.</p>

<h3>Proxy a Page</h3>
<form method="get" action="proxy.php"><div>
   <input type="hidden" name="xn_auth" value="no" />
   URL: <input type="text" name="url" />
   <input type="submit" value="Go" />
</div></form>

<h3>Setup a Page / Site</h3>
<p>Wildcard (*) globs are allowed.</p>
<form method="get" action="setup.php"><div>
   URL: <input type="text" name="url" />
   <input type="submit" value="Go" />
</div></form>