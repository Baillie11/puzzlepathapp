<?php
if(!defined('PUZZLE_PATH_ACCESS') || empty($booking)){http_response_code(403);exit;}
 // Rendered inside the authenticated shared dashboard shell. ?>
<style>
.leader-earned-art{position:relative;flex-shrink:0;padding-top:30px;margin:0 8px 10px 0;width:clamp(80px,14vw,128px)}
.leader-board:not(.featured) .leader-earned-art{width:clamp(64px,10vw,80px);padding-top:24px}
.leader-earned-art .leader-badge-sparkles{position:absolute;top:0;left:-8px;right:-8px;color:var(--purple);font-size:1.35rem;line-height:1;display:flex;justify-content:space-between}
.leader-board:not(.featured) .leader-badge-sparkles{font-size:1rem}
.leader-earned-art .badge-slot{width:100%;height:auto;aspect-ratio:2/3;padding:8px;background:#fff8d6;border:2px solid #e1b633;transform:rotate(3deg);box-shadow:0 6px 0 #dcae32}
.leader-earned-art .badge-slot img{width:100%;height:100%;max-width:none;max-height:none;object-fit:contain;border-radius:7px}
</style>
<section class="leader-intro"><span class="eyebrow">Big adventures. Friendly competition.</span><h1>The Puzzle Path hall of adventure ✦</h1><p>Your latest completed quest gets the spotlight. Scroll on to discover the fastest explorers across every quest!</p><a class="button" href="dashboard.php#badge-collection-title">← Back to your badge collection</a>
<?php if(!empty($repeatOffer['active'])):?><p class="discount-copy"><strong>Your next adventure is 25% off!</strong> Pick a quest below and your Repeat Hunter Discount is applied automatically. Book by <?=pdh(pddate($repeatOffer['expires_at']))?> using the same booking email.</p><?php else:?><p>Your 30-day 25% Repeat Hunter offer has ended. You can still pick your next adventure below at the current price.</p><?php endif;?></section>
<p class="muted">Fastest verified app completions. Equal times share a position. Test runs and manual completions appear privately as unranked; other explorers use anonymous labels.</p>
<div class="leader-grid">
<?php foreach($boards as $board):$q=$board['quest'];?><section class="leader-board<?=$board['featured']?' featured':''?>" id="<?=$board['featured']?'featured':'quest-'.(int)$q['id']?>" aria-labelledby="leader-title-<?= (int)$q['id']?>">
<div class="leader-card-heading" style="display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:16px"><div style="min-width:0"><span class="eyebrow"><?=$board['featured']?'★ Your latest conquest':'More clues. More glory.'?></span><h2 id="leader-title-<?= (int)$q['id']?>"><?=pdh($q['title'])?></h2><?php if($board['completed']):?><span class="badge">✓ Badge collected</span><?php endif;?></div>
<?php if($board['completed'] && pdbadgeurl($q['medal_image_url'])):?><div class="leader-earned-art"><span class="leader-badge-sparkles" aria-hidden="true"><span>✦</span><span>✧</span><span>★</span></span><div class="badge-slot"><img class="collected-quest-badge" src="<?=pdh(pdbadgeurl($q['medal_image_url']))?>" alt="<?=pdh($q['title'])?> — badge collected" width="384" height="576" loading="lazy" decoding="async"></div></div><?php endif;?></div>
<?php if($board['rows']):?><table><thead><tr><th scope="col">Position</th><th scope="col">Explorer</th><th scope="col">Time</th></tr></thead><tbody><?php foreach($board['rows'] as $r):?><tr<?=$r['own']?' class="own"':''?>><td><?=$r['rank']?'#'.pdh($r['rank']):'Unranked'?></td><td><?=pdh($r['name'])?><?php if($r['note']):?><small><?=pdh($r['note'])?></small><?php endif;?></td><td><?=pdh(pdtime($r['duration_ms']))?></td></tr><?php endforeach;?></tbody></table>
<?php else:?><p class="notice">No ranked times yet. The first spot could have your name on it! ✨</p><?php endif;?>
<div class="notice quest-description"><?=pdh(pdsummary($q['quest_description'],$q['location']))?></div>
<?php if(!$board['completed'] && $q['display_on_site'] && (int)$q['seats']>0):?>
<a class="button" target="_blank" rel="noopener noreferrer" href="<?=pdh(pdbookurl($q['id'],$repeatOffer))?>" aria-label="<?=pdh($q['title'])?> — book this quest">Chase this badge</a>
<?php elseif(!$board['completed']):?><p>Bookings are taking a little breather. Check back for your next adventure!</p><?php endif;?></section><?php endforeach;?></div>
