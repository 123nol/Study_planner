/* ============================================
   DASHBOARD CHARTS — Canvas renderers
   ============================================ */

(function () {
	const DPR = Math.max(1, window.devicePixelRatio || 1);

	function resizeCanvas(canvas, width, height) {
		const nextWidth = Math.max(1, Math.floor(width * DPR));
		const nextHeight = Math.max(1, Math.floor(height * DPR));

		if (canvas.width !== nextWidth) {
			canvas.width = nextWidth;
		}

		if (canvas.height !== nextHeight) {
			canvas.height = nextHeight;
		}

		const context = canvas.getContext('2d');
		context.setTransform(DPR, 0, 0, DPR, 0, 0);
		return context;
	}

	function clearCanvas(context, width, height) {
		context.clearRect(0, 0, width, height);
	}

	function roundedRect(context, x, y, width, height, radius) {
		const normalizedRadius = Math.min(radius, width / 2, height / 2);

		context.beginPath();
		context.moveTo(x + normalizedRadius, y);
		context.arcTo(x + width, y, x + width, y + height, normalizedRadius);
		context.arcTo(x + width, y + height, x, y + height, normalizedRadius);
		context.arcTo(x, y + height, x, y, normalizedRadius);
		context.arcTo(x, y, x + width, y, normalizedRadius);
		context.closePath();
	}

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function lerp(start, end, amount) {
		return start + (end - start) * amount;
	}

	function easeOutCubic(t) {
		return 1 - Math.pow(1 - t, 3);
	}

	function drawProgressRing(canvas, progress, options = {}) {
		if (!canvas) {
			return;
		}

		const rect = canvas.getBoundingClientRect();
		const width = rect.width || canvas.clientWidth || 320;
		const height = rect.height || canvas.clientHeight || 320;
		const context = resizeCanvas(canvas, width, height);
		const ratio = clamp(progress, 0, 100) / 100;
		const centerX = width / 2;
		const centerY = height / 2;
		const outerRadius = Math.min(width, height) * 0.32;
		const lineWidth = options.lineWidth || Math.max(16, Math.min(width, height) * 0.09);
		const startAngle = -Math.PI / 2;
		const endAngle = startAngle + Math.PI * 2 * ratio;
		const accentStart = options.accentStart || '#6366f1';
		const accentEnd = options.accentEnd || '#a855f7';
		const label = options.label || 'Completion';

		clearCanvas(context, width, height);

		const drawFrame = (currentRatio) => {
			const currentEndAngle = startAngle + Math.PI * 2 * currentRatio;

			context.clearRect(0, 0, width, height);

			context.beginPath();
			context.lineWidth = lineWidth;
			context.strokeStyle = 'rgba(255, 255, 255, 0.08)';
			context.lineCap = 'round';
			context.arc(centerX, centerY, outerRadius, 0, Math.PI * 2);
			context.stroke();

			const gradient = context.createLinearGradient(0, 0, width, height);
			gradient.addColorStop(0, accentStart);
			gradient.addColorStop(1, accentEnd);

			context.beginPath();
			context.lineWidth = lineWidth;
			context.strokeStyle = gradient;
			context.lineCap = 'round';
			context.shadowColor = 'rgba(99, 102, 241, 0.45)';
			context.shadowBlur = 18;
			context.arc(centerX, centerY, outerRadius, startAngle, currentEndAngle);
			context.stroke();
			context.shadowBlur = 0;

			const innerRadius = outerRadius - lineWidth / 2;
			const valueText = `${Math.round(currentRatio * 100)}%`;

			context.fillStyle = '#f8fafc';
			context.font = `700 ${Math.max(34, Math.round(width * 0.16))}px Outfit, sans-serif`;
			context.textAlign = 'center';
			context.textBaseline = 'middle';
			context.fillText(valueText, centerX, centerY - 8);

			context.fillStyle = '#94a3b8';
			context.font = `500 ${Math.max(12, Math.round(width * 0.04))}px Inter, sans-serif`;
			context.fillText(label, centerX, centerY + 28);

			context.fillStyle = '#64748b';
			context.font = `500 ${Math.max(11, Math.round(width * 0.032))}px Inter, sans-serif`;
			context.fillText(options.subLabel || 'Progress update', centerX, centerY + 48);

			context.beginPath();
			context.fillStyle = 'rgba(255, 255, 255, 0.03)';
			context.arc(centerX, centerY, Math.max(0, innerRadius - 4), 0, Math.PI * 2);
			context.fill();
		};

		if (options.animate === false) {
			drawFrame(ratio);
			return;
		}

		const duration = options.duration || 900;
		const startTime = performance.now();

		function animate(now) {
			const elapsed = now - startTime;
			const frameRatio = elapsed >= duration ? ratio : ratio * easeOutCubic(elapsed / duration);
			drawFrame(frameRatio);

			if (elapsed < duration) {
				requestAnimationFrame(animate);
			}
		}

		requestAnimationFrame(animate);
	}

	function drawBarChart(canvas, labels, values, options = {}) {
		if (!canvas) {
			return;
		}

		const rect = canvas.getBoundingClientRect();
		const width = rect.width || canvas.clientWidth || 700;
		const height = rect.height || canvas.clientHeight || 280;
		const context = resizeCanvas(canvas, width, height);
		const padding = {
			top: options.paddingTop || 36,
			right: options.paddingRight || 20,
			bottom: options.paddingBottom || 42,
			left: options.paddingLeft || 28,
		};
		const chartWidth = width - padding.left - padding.right;
		const chartHeight = height - padding.top - padding.bottom;
		const maxValue = Math.max(1, ...values);
		const barCount = Math.max(1, labels.length);
		const gap = options.gap || 14;
		const barWidth = Math.max(18, (chartWidth - gap * (barCount - 1)) / barCount);
		const accentStart = options.accentStart || '#6366f1';
		const accentEnd = options.accentEnd || '#8b5cf6';

		clearCanvas(context, width, height);

		context.fillStyle = '#94a3b8';
		context.font = `500 12px Inter, sans-serif`;
		context.textAlign = 'left';
		context.textBaseline = 'middle';

		const gridLines = 4;
		for (let index = 0; index <= gridLines; index += 1) {
			const value = Math.round((maxValue / gridLines) * (gridLines - index));
			const y = padding.top + (chartHeight / gridLines) * index;

			context.strokeStyle = 'rgba(148, 163, 184, 0.14)';
			context.lineWidth = 1;
			context.beginPath();
			context.moveTo(padding.left, y);
			context.lineTo(width - padding.right, y);
			context.stroke();

			context.fillStyle = '#64748b';
			context.fillText(String(value), 4, y);
		}

		labels.forEach((label, index) => {
			const value = values[index] || 0;
			const barHeight = (value / maxValue) * chartHeight;
			const x = padding.left + index * (barWidth + gap);
			const y = padding.top + chartHeight - barHeight;

			const gradient = context.createLinearGradient(0, y, 0, y + barHeight);
			gradient.addColorStop(0, accentEnd);
			gradient.addColorStop(1, accentStart);

			context.save();
			context.shadowColor = 'rgba(99, 102, 241, 0.25)';
			context.shadowBlur = 12;
			context.fillStyle = gradient;
			roundedRect(context, x, y, barWidth, Math.max(4, barHeight), 12);
			context.fill();
			context.restore();

			context.fillStyle = '#e2e8f0';
			context.font = `600 12px Inter, sans-serif`;
			context.textAlign = 'center';
			context.textBaseline = 'bottom';
			context.fillText(String(value), x + barWidth / 2, Math.max(18, y - 8));

			context.fillStyle = '#94a3b8';
			context.font = `500 11px Inter, sans-serif`;
			context.textAlign = 'center';
			context.textBaseline = 'top';
			context.fillText(label, x + barWidth / 2, height - padding.bottom + 10);
		});

		if (!values.length || values.every((value) => value === 0)) {
			context.fillStyle = 'rgba(148, 163, 184, 0.85)';
			context.font = `500 14px Inter, sans-serif`;
			context.textAlign = 'center';
			context.textBaseline = 'middle';
			context.fillText(options.emptyLabel || 'No activity yet', width / 2, height / 2);
		}
	}

	window.DashboardCharts = {
		drawProgressRing,
		drawBarChart,
	};
})();
