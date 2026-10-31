<?php /** @var \Io\Live\Tests\Fixtures\Visitor $this */ ?>
<p><?= $this->connection->request->cookies['who'] ?? 'nobody' ?></p><button data-on-click="leave">leave</button><i><?= htmlspecialchars($this->connection->page) ?></i>
