<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>
    <template #filter>
      <input type="text" class="hidden">
      <input
          :id="id"
          class="form-control form-control-sm form-input form-input-bordered bg-gray-100 text-sm px-1"
          type="text"
          :dusk="`${filter.name}-daterange-filter`"
          name="daterangefilter"
          autocomplete="off"
          :value="value"
          :placeholder="placeholder"
          @keydown="handleInput($event)"
          @paste.prevent
          :style="{ width: showTime ? '265px' : '185px' }"
      />
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce';

export default {
  emits: ['change'],

  props: {
    resourceName: {
      type: String,
      required: true,
    },
    filterKey: {
      type: String,
      required: true,
    },
    lens: String,
  },

  data: () => ({
    id: null,
    value: null,
    startDate: null,
    endDate: null,
    currentStartDate: null,
    currentEndDate: null,
    debouncedHandleChange: null,
    currentRanges: null,
    maxDate: null,
    minDate: null,
    showTime: null,
  }),

  created() {
    this.debouncedHandleChange = debounce(() => this.handleChange(), 500);

    this.setCurrentFilterValue();
    this.setOptions();
    this.parseDates();
  },

  mounted() {
    this.id = 'dateRangeCalendar_' + this.generateId();

    Nova.$on('filter-reset', this.setCurrentFilterValue);

    setTimeout(() => {
      this.initDateRange();
    }, 1);
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue);
  },

  watch: {
    value() {
      this.debouncedHandleChange();
    },
  },

  methods: {
    getDateFormat() {
      return this.showTime ? 'DD-MM-YYYY HH:mm' : 'DD-MM-YYYY';
    },
    setOptions() {
      var customRanges = JSON.parse(this.filter.options.find(opt => opt.label === 'customRanges').value);

      Object.keys(customRanges).forEach(function(key) {
        var datesArray = customRanges[key];
        var momentsArray = datesArray.map(function(dateString) {
          return moment(dateString);
        });
        customRanges[key] = momentsArray;
      });

      this.customRanges = customRanges;

      this.maxDate = this.filter.options.find(opt => opt.label === 'maxDate').value ?
          moment(this.filter.options.find(opt => opt.label === 'maxDate').value) :
          false;

      this.minDate = this.filter.options.find(opt => opt.label === 'minDate').value ?
          moment(this.filter.options.find(opt => opt.label === 'minDate').value) :
          false;

      this.showTime = this.filter.options.find(opt => opt.label === 'showTime').value;
    },
    setCurrentFilterValue() {
      this.value = this.filter.currentValue;
    },
    handleChange() {
      const format = this.getDateFormat();

      this.$store.commit(`${this.resourceName}/updateFilterState`, {
        filterClass: this.filterKey,
        value: this.currentStartDate.format(format) + ' - ' + this.currentEndDate.format(format),
      });

      this.$emit('change');

      this.currentStartDate = null;
      this.currentEndDate = null;
    },
    handleInput(e) {
      return e.preventDefault();
    },
    initDateRange() {
      const idSelector = '#' + this.id;
      const ref = this;

      $(idSelector).daterangepicker({
        timePicker: ref.showTime,
        timePicker24Hour: true,
        startDate: ref.currentStartDate,
        endDate: ref.currentEndDate,
        maxDate: ref.maxDate,
        minDate: ref.minDate,
        ranges: ref.customRanges,
        locale: {
          format: ref.getDateFormat(),
        },
      }, function(start, end, label) {
        if (start && end) {
          ref.currentStartDate = start;
          ref.currentEndDate = end;
        }
      })
          .on('apply.daterangepicker', function(ev, picker) {
            if (ref.currentStartDate && ref.currentEndDate) {
              // Важно — берем время из picker, а не из parseDates
              ref.currentStartDate = picker.startDate.clone();
              ref.currentEndDate = picker.endDate.clone();

              ref.value = ref.currentStartDate.format(ref.getDateFormat()) +
                  ' - ' +
                  ref.currentEndDate.format(ref.getDateFormat());
            }
          });
    },
    generateId: function() {
      return Math.random().toString(36).substring(2) +
          (new Date()).getTime().toString(36);
    },
    parseDates() {
      const dateRange = this.filter.currentValue;
      const format = this.getDateFormat();

      let startDate = moment();
      let endDate = this.showTime ? moment() : moment().endOf('day');

      if (dateRange) {
        const parsedDateRange = dateRange.split(' - ');
        if (parsedDateRange.length === 2) {
          startDate = moment(parsedDateRange[0], format);
          endDate = moment(parsedDateRange[1], format);

          if (!this.showTime) {
            endDate = endDate.endOf('day');
          }
        }
      }

      this.startDate = startDate;
      this.endDate = endDate;
      this.currentStartDate = startDate;
      this.currentEndDate = endDate;
    },
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
          this.filterKey,
      );
    },
  },
};
</script>
