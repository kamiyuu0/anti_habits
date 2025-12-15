// Entry point for the build script in your package.json
import "@hotwired/turbo-rails"
import "./controllers"

// echartsをグローバルに公開
import * as echarts from 'echarts';
window.echarts = echarts;
