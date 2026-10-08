import { Controller } from '@hotwired/stimulus';
// 活動記録ヒートマップ (Rails 版の rails_charts calendar_chart 相当)
export default class extends Controller {
    static values = {
        data: Array,
        start: String,
        end: String,
    };

    async connect() {
        // ECharts はサイズが大きいため、ヒートマップを表示するページでのみ読み込む
        const { default: echarts } = await import('../lib/echarts_calendar');
        if (!this.element.isConnected) return;

        this.chart = echarts.init(this.element);
        this.chart.setOption({
            tooltip: {
                position: 'top',
                formatter: (params) => params.data[0],
            },
            visualMap: {
                show: false,
                min: 0,
                max: 1,
                inRange: { color: ['#ebedf0', '#216e39'] },
            },
            calendar: {
                top: 30,
                left: 'center',
                right: 10,
                cellSize: ['auto', 13],
                range: [this.startValue, this.endValue],
                itemStyle: { borderWidth: 3, borderColor: '#fff' },
                yearLabel: { show: false },
                monthLabel: {
                    nameMap: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                    fontSize: 10,
                },
                dayLabel: {
                    firstDay: 1,
                    nameMap: ['日', '月', '火', '水', '木', '金', '土'],
                    fontSize: 10,
                },
                splitLine: { show: true, lineStyle: { color: '#f0f0f0', width: 1 } },
            },
            series: [{ type: 'heatmap', coordinateSystem: 'calendar', data: this.dataValue }],
        });

        this.resize = () => this.chart.resize();
        window.addEventListener('resize', this.resize);
    }

    disconnect() {
        window.removeEventListener('resize', this.resize);
        this.chart?.dispose();
    }
}
