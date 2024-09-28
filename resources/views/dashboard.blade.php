@extends('layouts.main')

@section('title', 'Dashboard')

@section('content')

    <div class="flex items-center ">
        <div class="h2_akm w-80 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                <path
                    d="M120-120v-80l80-80v160h-80Zm160 0v-240l80-80v320h-80Zm160 0v-320l80 81v239h-80Zm160 0v-239l80-80v319h-80Zm160 0v-400l80-80v480h-80ZM120-327v-113l280-280 160 160 280-280v113L560-447 400-607 120-327Z" />
            </svg>
            <span>Dashboard </span>
        </div>
    </div>
    <div class="divider"></div>

    <div>

        <div>
            <div class="h3_akm mb-4">Meal Quantity Ordered Per Day </div>
            <div id="chart"></div>
        </div>
        <div>
            <div class="h3_akm mb-4">Lunch/Dinner Quantity Ordered Per Day </div>
            <div id="lunchDinnerChart"></div>
        </div>


        <div>
            <div class="h3_akm mb-4">User Registered Per Day </div>


            <div id="userQuantitiesChart"></div>
        </div>

    </div>

    <script>
        var ordersPerDateData = @json($ordersPerDateData);
        var ldPerDateData = @json($ldPerDateData);
        var userRegPerDay = @json($userRegPerDay);
    </script>
    <!-- Include ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare chart data
            const dates = ordersPerDateData.dates;
            const quantities = ordersPerDateData.quantities;

            // Initialize the chart
            var options = {
                series: [{
                    name: 'Quantity',
                    data: quantities
                }],
                chart: {
                    type: 'bar',
                    height: 150,
                    background: '#333' // Optional: Set a dark background for better contrast
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        colors: {
                            ranges: [{
                                from: 0,
                                to: 100, // Adjust this range as needed
                                color: '#004225' // Set your desired bar color here
                            }]
                        }
                    }
                },
                xaxis: {
                    categories: dates,

                    labels: {
                        style: {
                            colors: '#fff' // White text color for x-axis labels
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Quantity',
                        style: {
                            color: '#fff' // White text color for the y-axis title
                        }
                    },
                    labels: {
                        style: {
                            colors: '#fff' // White text color for y-axis labels
                        }
                    }
                },

                legend: {
                    labels: {
                        colors: '#fff' // White text color for legend labels
                    }
                },
                tooltip: {
                    theme: 'dark' // Optional: Dark tooltip for better visibility
                }
            };

            var chart = new ApexCharts(document.querySelector("#chart"), options);
            chart.render();
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const dates = ldPerDateData.dates;
            const lunchQuantities = ldPerDateData.lunchQuantities;
            const dinnerQuantities = ldPerDateData.dinnerQuantities;

            var lunchDinnerOptions = {
                chart: {
                    type: 'bar',
                    height: 150,
                    background: '#333'
                },
                series: [{
                    name: 'Lunch Orders',
                    data: lunchQuantities,
                    color: '#FF7300'

                }, {
                    name: 'Dinner Orders',
                    data: dinnerQuantities,
                    color: '#0c1445'

                }],
                xaxis: {
                    categories: dates,
                    labels: {
                        style: {
                            colors: '#fff' // White text color for x-axis labels
                        }
                    }

                },
                yaxis: {
                    title: {
                        text: ' Quantity',
                        style: {
                            color: '#fff' // White text color for the y-axis title
                        }
                    }
                },

                legend: {
                    show: false,
                    labels: {
                        colors: '#fff' // White text color for legend labels
                    }
                },
                tooltip: {
                    theme: 'dark' // Optional: Dark tooltip for better visibility
                },
                plotOptions: {
                    bar: {
                        grouped: true
                    }
                }
            };

            var lunchDinnerChart = new ApexCharts(document.querySelector("#lunchDinnerChart"), lunchDinnerOptions);
            lunchDinnerChart.render();
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const dates = userRegPerDay.dates; // Assuming this data is defined
            const userQuantities = userRegPerDay.userQuantities; // Assuming this data is defined

            var userQuantitiesOptions = {
                chart: {
                    type: 'bar',
                    height: 150,
                    background: '#333'
                },
                series: [{
                    name: 'User Quantities',
                    data: userQuantities,
                    // You can customize the color
                }],
                xaxis: {
                    categories: dates,
                    labels: {
                        style: {
                            colors: '#fff' // White text color for x-axis labels
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'User Number',
                        style: {
                            color: '#fff' // White text color for the y-axis title
                        }
                    }
                },
                legend: {
                    show: true,
                    labels: {
                        colors: '#fff' // White text color for legend labels
                    }
                },
                tooltip: {
                    theme: 'dark' // Optional: Dark tooltip for better visibility
                },
                plotOptions: {
                    bar: {
                        horizontal: false, // Change to true for horizontal bars
                        endingShape: 'rounded'
                    }
                }
            };

            var userQuantitiesChart = new ApexCharts(document.querySelector("#userQuantitiesChart"),
                userQuantitiesOptions);
            userQuantitiesChart.render();
        });
    </script>
@endsection
