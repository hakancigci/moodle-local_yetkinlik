define(['jquery', 'core/chartjs'], function($, Chart) {
    "use strict";

    return {
        initStudentClass: function(params) {
            var data = Array.isArray(params) ? params[0] : params;
            var canvas = document.getElementById('studentClassChart');
            if (!canvas || !data) {
                return;
            }

            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {label: data.labelNames.course, data: data.courseData, backgroundColor: 'rgba(156, 39, 176, 0.4)'},
                        {label: data.labelNames.class, data: data.classData, backgroundColor: 'rgba(76, 175, 80, 0.4)'},
                        {label: data.labelNames.my, data: data.myData, backgroundColor: 'rgba(33, 150, 243, 0.8)'}
                    ]
                },
                options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, max: 100}}}
            });
        },

        initStudentExam: function(params) {
            var data = Array.isArray(params) ? params[0] : params;
            var canvas = document.getElementById('studentexamchart');
            if (!canvas || !data) {
                return;
            }

            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: data.chartLabel,
                        data: data.chartData,
                        backgroundColor: data.bgColors
                    }]
                },
                options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, max: 100}}}
            });
        },

        initTimeline: function(params) {
            var data = Array.isArray(params) ? params[0] : params;
            var canvas = document.getElementById('timeline');
            if (!canvas || !data) {
                return;
            }

            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {beginAtZero: true, max: 100, title: {display: true, text: data.successLabel + ' (%)'}}
                    },
                    plugins: {legend: {position: 'bottom'}}
                }
            });
        }
    };
});