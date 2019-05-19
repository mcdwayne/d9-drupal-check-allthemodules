'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

(function ($, Toolshed) {
  Drupal.behaviors.toolshedAccordions = {
    accordions: [],

    attach: function attach(context, settings) {
      var _this = this;

      $('.use-accordion', context).once('accordion').each(function (i, accordion) {
        _this.accordions.push(new Toolshed.Accordion($(accordion), _extends({}, settings.Toolshed.accordions, {
          exclusive: true,
          initOpen: false
        })));
      });
    }
  };
})(jQuery, Drupal.Toolshed);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImJlaGF2aW9yL2FjY29yZGlvbnMuZXM2LmpzIl0sIm5hbWVzIjpbIiQiLCJUb29sc2hlZCIsIkRydXBhbCIsImJlaGF2aW9ycyIsInRvb2xzaGVkQWNjb3JkaW9ucyIsImFjY29yZGlvbnMiLCJhdHRhY2giLCJjb250ZXh0Iiwic2V0dGluZ3MiLCJvbmNlIiwiZWFjaCIsImkiLCJhY2NvcmRpb24iLCJwdXNoIiwiQWNjb3JkaW9uIiwiZXhjbHVzaXZlIiwiaW5pdE9wZW4iLCJqUXVlcnkiXSwibWFwcGluZ3MiOiI7Ozs7QUFDQSxDQUFDLFVBQUNBLENBQUQsRUFBSUMsUUFBSixFQUFpQjtBQUNoQkMsU0FBT0MsU0FBUCxDQUFpQkMsa0JBQWpCLEdBQXNDO0FBQ3BDQyxnQkFBWSxFQUR3Qjs7QUFHcENDLFVBSG9DLGtCQUc3QkMsT0FINkIsRUFHcEJDLFFBSG9CLEVBR1Y7QUFBQTs7QUFDeEJSLFFBQUUsZ0JBQUYsRUFBb0JPLE9BQXBCLEVBQTZCRSxJQUE3QixDQUFrQyxXQUFsQyxFQUErQ0MsSUFBL0MsQ0FBb0QsVUFBQ0MsQ0FBRCxFQUFJQyxTQUFKLEVBQWtCO0FBQ3BFLGNBQUtQLFVBQUwsQ0FBZ0JRLElBQWhCLENBQXFCLElBQUlaLFNBQVNhLFNBQWIsQ0FBdUJkLEVBQUVZLFNBQUYsQ0FBdkIsZUFDaEJKLFNBQVNQLFFBQVQsQ0FBa0JJLFVBREY7QUFFbkJVLHFCQUFXLElBRlE7QUFHbkJDLG9CQUFVO0FBSFMsV0FBckI7QUFLRCxPQU5EO0FBT0Q7QUFYbUMsR0FBdEM7QUFhRCxDQWRELEVBY0dDLE1BZEgsRUFjV2YsT0FBT0QsUUFkbEIiLCJmaWxlIjoiYmVoYXZpb3IvYWNjb3JkaW9ucy5qcyIsInNvdXJjZXNDb250ZW50IjpbIlxuKCgkLCBUb29sc2hlZCkgPT4ge1xuICBEcnVwYWwuYmVoYXZpb3JzLnRvb2xzaGVkQWNjb3JkaW9ucyA9IHtcbiAgICBhY2NvcmRpb25zOiBbXSxcblxuICAgIGF0dGFjaChjb250ZXh0LCBzZXR0aW5ncykge1xuICAgICAgJCgnLnVzZS1hY2NvcmRpb24nLCBjb250ZXh0KS5vbmNlKCdhY2NvcmRpb24nKS5lYWNoKChpLCBhY2NvcmRpb24pID0+IHtcbiAgICAgICAgdGhpcy5hY2NvcmRpb25zLnB1c2gobmV3IFRvb2xzaGVkLkFjY29yZGlvbigkKGFjY29yZGlvbiksIHtcbiAgICAgICAgICAuLi5zZXR0aW5ncy5Ub29sc2hlZC5hY2NvcmRpb25zLFxuICAgICAgICAgIGV4Y2x1c2l2ZTogdHJ1ZSxcbiAgICAgICAgICBpbml0T3BlbjogZmFsc2UsXG4gICAgICAgIH0pKTtcbiAgICAgIH0pO1xuICAgIH0sXG4gIH07XG59KShqUXVlcnksIERydXBhbC5Ub29sc2hlZCk7XG4iXX0=
