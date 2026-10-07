import '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import AutocompleteController from './controllers/autocomplete_controller';
import TagifyController from './controllers/tagify_controller';
import CalendarChartController from './controllers/calendar_chart_controller';

const application = Application.start();
application.debug = false;
window.Stimulus = application;

application.register('autocomplete', AutocompleteController);
application.register('tagify', TagifyController);
application.register('calendar-chart', CalendarChartController);
