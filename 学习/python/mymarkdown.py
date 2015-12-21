# -*- coding:utf-8 -*-
from django.contrib.auth.models import User
from django.db import models
import markdown2


STATUS = {
    0: u'正常',
    1: u'删除',
    2: u'草稿',
}


class Category(models.Model):
    name = models.CharField(max_length=30, verbose_name=u'名称')
    en_name = models.CharField(max_length=40, verbose_name=u'英文名称')
    des = models.CharField(max_length=100, verbose_name=u'分类描述')
    status = models.IntegerField(default=0, choices=STATUS.items(), verbose_name=u'状态')
    rank = models.IntegerField(default=0, verbose_name=u'排序')
    create_time = models.DateTimeField(auto_now_add=True, verbose_name=u'创建时间')

    def __unicode__(self):
        return self.name

    class Meta:
        verbose_name_plural = verbose_name = u'分类管理'
        ordering = ['rank', '-create_time', ]


class Tag(models.Model):
    name = models.CharField(max_length=30, verbose_name=u'名称')
    en_name = models.CharField(max_length=40, verbose_name=u'英文名称')
    status = models.IntegerField(default=0, choices=STATUS.items(), verbose_name=u'状态')
    rank = models.IntegerField(default=0, verbose_name=u'排序')
    create_time = models.DateTimeField(auto_now_add=True, verbose_name=u'创建时间')

    def __unicode__(self):
        return self.name

    class Meta:
        verbose_name_plural = verbose_name = u'标签管理'
        ordering = ['rank', '-create_time', ]


class Article(models.Model):
    author = models.ForeignKey(User, verbose_name=u'作者')
    title = models.CharField(max_length=40, verbose_name=u'标题')
    en_title = models.CharField(max_length=40, verbose_name=u'英文标题')
    category = models.ForeignKey(Category, verbose_name=u'分类')
    tag = models.ManyToManyField(Tag, blank=True, verbose_name=u'标签')
    summary = models.TextField(verbose_name=u'摘要')
    content = models.TextField(verbose_name=u'内容')
    content_html = models.TextField(editable=False, blank=True, null=True)
    view_time = models.IntegerField(editable=False, default=0, verbose_name=u'访问次数')
    last_accessed = models.DateTimeField(editable=False, null=True, verbose_name=u'最近访问时间')
    status = models.IntegerField(default=0, choices=STATUS.items(), verbose_name=u'状态')
    rank = models.IntegerField(default=0, verbose_name=u'排序')

    pub_time = models.DateTimeField(default=False, verbose_name=u'发布时间')
    create_time = models.DateTimeField(auto_now_add=True, verbose_name=u'创建时间')
    update_time = models.DateTimeField(auto_now=True, verbose_name=u'更新时间')

    def save(self, force_insert=False, force_update=False, using=None,
             update_fields=None):
        self.content_html = markdown2.markdown(self.content, extras=['fenced-code-blocks']).encode('utf-8')
        super(Article, self).save()

    def __unicode__(self):
        return self.title

    class Meta:
        verbose_name_plural = verbose_name = u'文章管理'
        ordering = ['rank', '-create_time', ]
